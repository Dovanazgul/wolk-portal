<?php

declare(strict_types=1);

$localMailConfig = [
    'driver' => 'smtp',
    'host' => 'smtp.hostinger.com',
    'port' => 465,
    'encryption' => 'ssl',
    'auth' => true,
    'username' => 'support-portal@dovagames.com',
    'password' => '/OmA4K02sT7',
    'from_email' => 'support-portal@dovagames.com',
    'from_name' => app_visible_name(),
    'reply_to_email' => 'support-portal@dovagames.com',
    'reply_to_name' => 'Soporte del Portal Interno',
];

$productionMailConfig = [
    'driver' => 'smtp',
    'host' => 'smtp-relay.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => false,
    'username' => '',
    'password' => '',
    'from_email' => 'support-portal@wolk-it.com',
    'from_name' => app_visible_name(),
    'reply_to_email' => 'support-portal@wolk-it.com',
    'reply_to_name' => 'Soporte del Portal Interno',
];

$mailConfig = is_production() ? $productionMailConfig : $localMailConfig;

function mail_config(string $key, mixed $default = null): mixed
{
    global $mailConfig;

    return $mailConfig[$key] ?? $default;
}

function nexus_send_mail(string $to, string $subject, string $htmlBody, string $plainBody = ''): bool
{
    $host = (string) mail_config('host');
    $port = (int) mail_config('port');
    $encryption = (string) mail_config('encryption');
    $auth = (bool) mail_config('auth');
    $username = (string) mail_config('username');
    $password = (string) mail_config('password');
    $fromEmail = (string) mail_config('from_email');
    $fromName = (string) mail_config('from_name');
    $replyToEmail = (string) mail_config('reply_to_email');
    $replyToName = (string) mail_config('reply_to_name');

    if ($auth && ($username === '' || $password === '')) {
        return false;
    }

    $boundary = 'portal_' . bin2hex(random_bytes(16));

    if ($plainBody === '') {
        $plainBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
    }

    $headers = [];
    $headers[] = 'From: ' . encode_mail_header($fromName) . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . encode_mail_header($replyToName) . ' <' . $replyToEmail . '>';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $message = '';
    $message .= '--' . $boundary . "\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $plainBody . "\r\n\r\n";

    $message .= '--' . $boundary . "\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $htmlBody . "\r\n\r\n";

    $message .= '--' . $boundary . "--\r\n";

    return smtp_send(
        $host,
        $port,
        $encryption,
        $auth,
        $username,
        $password,
        $fromEmail,
        $to,
        $subject,
        implode("\r\n", $headers),
        $message
    );
}

function smtp_send(
    string $host,
    int $port,
    string $encryption,
    bool $auth,
    string $username,
    string $password,
    string $from,
    string $to,
    string $subject,
    string $headers,
    string $message
): bool {
    $remoteHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
    $socket = fsockopen($remoteHost, $port, $errno, $errstr, 25);

    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 25);

    if (!smtp_expect($socket, 220)) {
        fclose($socket);
        return false;
    }

    smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));

    if (!smtp_expect($socket, 250)) {
        fclose($socket);
        return false;
    }

    if ($encryption === 'tls' || $encryption === 'starttls') {
        smtp_command($socket, 'STARTTLS');

        if (!smtp_expect($socket, 220)) {
            fclose($socket);
            return false;
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }

        smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));

        if (!smtp_expect($socket, 250)) {
            fclose($socket);
            return false;
        }
    }

    if ($auth) {
        smtp_command($socket, 'AUTH LOGIN');

        if (!smtp_expect($socket, 334)) {
            fclose($socket);
            return false;
        }

        smtp_command($socket, base64_encode($username));

        if (!smtp_expect($socket, 334)) {
            fclose($socket);
            return false;
        }

        smtp_command($socket, base64_encode($password));

        if (!smtp_expect($socket, 235)) {
            fclose($socket);
            return false;
        }
    }

    smtp_command($socket, 'MAIL FROM:<' . $from . '>');

    if (!smtp_expect($socket, 250)) {
        fclose($socket);
        return false;
    }

    smtp_command($socket, 'RCPT TO:<' . $to . '>');

    if (!smtp_expect($socket, [250, 251])) {
        fclose($socket);
        return false;
    }

    smtp_command($socket, 'DATA');

    if (!smtp_expect($socket, 354)) {
        fclose($socket);
        return false;
    }

    $emailData = '';
    $emailData .= 'To: <' . $to . '>' . "\r\n";
    $emailData .= 'Subject: ' . encode_mail_header($subject) . "\r\n";
    $emailData .= $headers . "\r\n\r\n";
    $emailData .= $message . "\r\n.";

    smtp_command($socket, $emailData);

    if (!smtp_expect($socket, 250)) {
        fclose($socket);
        return false;
    }

    smtp_command($socket, 'QUIT');
    fclose($socket);

    return true;
}

function smtp_command($socket, string $command): void
{
    fwrite($socket, $command . "\r\n");
}

function smtp_expect($socket, int|array $expectedCodes): bool
{
    $expectedCodes = is_array($expectedCodes) ? $expectedCodes : [$expectedCodes];
    $response = '';

    while ($line = fgets($socket, 515)) {
        $response .= $line;

        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);

    return in_array($code, $expectedCodes, true);
}

function encode_mail_header(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function portal_mail_layout(string $title, string $content): string
{
    $systemName = app_visible_name();

    return '
        <div style="font-family:Arial,sans-serif;background:#f4f7fa;padding:28px;color:#0f172a;">
            <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #dbe5ec;border-radius:18px;padding:28px;">
                <div style="margin-bottom:22px;">
                    <strong style="font-size:19px;color:#0f172a;">' . e($systemName) . '</strong>
                    <div style="font-size:13px;color:#607084;margin-top:3px;">
                        Accesos, documentos y recursos internos de Wolk IT.
                    </div>
                </div>

                <h1 style="margin:0 0 16px;color:#0f172a;font-size:24px;line-height:1.2;">
                    ' . e($title) . '
                </h1>

                ' . $content . '

                <div style="margin-top:26px;padding-top:18px;border-top:1px solid #dbe5ec;">
                    <p style="margin:0;color:#607084;font-size:13px;line-height:1.6;">
                        Este mensaje fue enviado automáticamente por ' . e($systemName) . '.
                    </p>
                </div>
            </div>
        </div>
    ';
}

function nexus_mail_layout(string $title, string $content): string
{
    return portal_mail_layout($title, $content);
}

function password_reset_email_html(string $name, string $resetUrl): string
{
    $content = '
        <p style="font-size:15px;line-height:1.7;margin:0 0 14px;">
            Hola, <strong>' . e($name) . '</strong>.
        </p>

        <p style="font-size:15px;line-height:1.7;margin:0 0 14px;">
            Recibimos una solicitud para restablecer tu contraseña del Portal interno.
            Para continuar, usa el botón siguiente.
        </p>

        <p style="margin:24px 0;">
            <a
                href="' . e($resetUrl) . '"
                style="display:inline-block;background:#0ea5a8;color:#ffffff;padding:13px 18px;border-radius:12px;text-decoration:none;font-weight:bold;"
            >
                Cambiar contraseña
            </a>
        </p>

        <p style="font-size:15px;line-height:1.7;margin:0 0 14px;">
            El enlace estará disponible por tiempo limitado. Si tú no solicitaste este cambio,
            no necesitas realizar ninguna acción.
        </p>

        <p style="font-size:13px;color:#607084;word-break:break-all;margin-top:18px;">
            ' . e($resetUrl) . '
        </p>
    ';

    return portal_mail_layout('Restablecer contraseña', $content);
}

function password_reset_email_text(string $name, string $resetUrl): string
{
    return "Hola {$name},\n\n"
        . "Recibimos una solicitud para restablecer tu contraseña del Portal interno.\n\n"
        . "Abre este enlace para cambiar tu contraseña:\n{$resetUrl}\n\n"
        . "Si tú no solicitaste este cambio, puedes ignorar este mensaje.\n\n"
        . app_visible_name();
}

function email_verification_html(string $name, string $verificationUrl): string
{
    $content = '
        <p style="font-size:15px;line-height:1.7;margin:0 0 14px;">
            Hola, <strong>' . e($name) . '</strong>.
        </p>

        <p style="font-size:15px;line-height:1.7;margin:0 0 14px;">
            Para completar la validación de tu cuenta en el Portal interno,
            confirma tu correo usando el botón siguiente.
        </p>

        <p style="margin:24px 0;">
            <a
                href="' . e($verificationUrl) . '"
                style="display:inline-block;background:#37b24d;color:#ffffff;padding:13px 18px;border-radius:12px;text-decoration:none;font-weight:bold;"
            >
                Verificar correo
            </a>
        </p>

        <p style="font-size:15px;line-height:1.7;margin:0 0 14px;">
            Esta verificación ayuda a proteger el acceso a la información interna.
        </p>

        <p style="font-size:13px;color:#607084;word-break:break-all;margin-top:18px;">
            ' . e($verificationUrl) . '
        </p>
    ';

    return portal_mail_layout('Verificación de correo', $content);
}

function email_verification_text(string $name, string $verificationUrl): string
{
    return "Hola {$name},\n\n"
        . "Para completar la validación de tu cuenta en el Portal interno, confirma tu correo.\n\n"
        . "Abre este enlace para verificar tu correo:\n{$verificationUrl}\n\n"
        . app_visible_name();
}
