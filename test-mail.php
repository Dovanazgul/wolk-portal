<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/mail.php';

$testTo = 'ignacio.mendoza@wolk-it.com';
$steps = [];
$error = '';
$mailSent = false;

function add_step(array &$steps, string $label, string $status, string $detail = ''): void
{
    $steps[] = [
        'label' => $label,
        'status' => $status,
        'detail' => $detail,
    ];
}

function read_smtp_response($socket): string
{
    $response = '';

    while ($line = fgets($socket, 515)) {
        $response .= $line;

        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    return trim($response);
}

function send_smtp_command($socket, string $command): string
{
    fwrite($socket, $command . "\r\n");

    return read_smtp_response($socket);
}

try {
    $host = (string) mail_config('host');
    $port = (int) mail_config('port');
    $encryption = (string) mail_config('encryption');
    $username = (string) mail_config('username');
    $password = (string) mail_config('password');
    $fromEmail = (string) mail_config('from_email');
    $fromName = (string) mail_config('from_name');
    $replyToEmail = (string) mail_config('reply_to_email');
    $replyToName = (string) mail_config('reply_to_name');

    if ($username === '' || $password === '' || $password === 'PEGA_AQUI_TU_PASSWORD_DE_HOSTINGER') {
        throw new RuntimeException('Falta colocar usuario o contraseña real en config/mail.php.');
    }

    $remoteHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;

    add_step($steps, 'Configuración detectada', 'ok', "{$host}:{$port} / {$encryption} / {$username}");

    $socket = fsockopen($remoteHost, $port, $errno, $errstr, 25);

    if (!$socket) {
        throw new RuntimeException("No se pudo conectar al SMTP. Error {$errno}: {$errstr}");
    }

    stream_set_timeout($socket, 25);

    add_step($steps, 'Conexión SMTP', 'ok', 'Conexión abierta correctamente.');

    $response = read_smtp_response($socket);

    if (!str_starts_with($response, '220')) {
        throw new RuntimeException('Respuesta inicial inesperada: ' . $response);
    }

    add_step($steps, 'Respuesta inicial', 'ok', $response);

    $response = send_smtp_command($socket, 'EHLO localhost');

    if (!str_starts_with($response, '250')) {
        throw new RuntimeException('EHLO falló: ' . $response);
    }

    add_step($steps, 'EHLO', 'ok', $response);

    if ($encryption === 'tls' || $encryption === 'starttls') {
        $response = send_smtp_command($socket, 'STARTTLS');

        if (!str_starts_with($response, '220')) {
            throw new RuntimeException('STARTTLS falló: ' . $response);
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('No se pudo activar TLS.');
        }

        add_step($steps, 'STARTTLS', 'ok', 'TLS activado correctamente.');

        $response = send_smtp_command($socket, 'EHLO localhost');

        if (!str_starts_with($response, '250')) {
            throw new RuntimeException('EHLO después de TLS falló: ' . $response);
        }
    }

    $response = send_smtp_command($socket, 'AUTH LOGIN');

    if (!str_starts_with($response, '334')) {
        throw new RuntimeException('AUTH LOGIN falló: ' . $response);
    }

    add_step($steps, 'AUTH LOGIN', 'ok', $response);

    $response = send_smtp_command($socket, base64_encode($username));

    if (!str_starts_with($response, '334')) {
        throw new RuntimeException('Usuario SMTP rechazado: ' . $response);
    }

    add_step($steps, 'Usuario SMTP', 'ok', 'Usuario aceptado.');

    $response = send_smtp_command($socket, base64_encode($password));

    if (!str_starts_with($response, '235')) {
        throw new RuntimeException('Contraseña SMTP rechazada: ' . $response);
    }

    add_step($steps, 'Contraseña SMTP', 'ok', 'Autenticación aceptada.');

    $response = send_smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>');

    if (!str_starts_with($response, '250')) {
        throw new RuntimeException('MAIL FROM rechazado: ' . $response);
    }

    add_step($steps, 'Remitente', 'ok', $response);

    $response = send_smtp_command($socket, 'RCPT TO:<' . $testTo . '>');

    if (!str_starts_with($response, '250') && !str_starts_with($response, '251')) {
        throw new RuntimeException('Destinatario rechazado: ' . $response);
    }

    add_step($steps, 'Destinatario', 'ok', $response);

    $response = send_smtp_command($socket, 'DATA');

    if (!str_starts_with($response, '354')) {
        throw new RuntimeException('DATA rechazado: ' . $response);
    }

    $subject = encode_mail_header('Prueba de correo | Wolk Nexus');
    $boundary = 'nexus_test_' . bin2hex(random_bytes(8));
    $testUrl = app_url('auth/login.php');

    $html = nexus_mail_layout(
        'Prueba de correo',
        '
            <p>Hola, <strong>Naz</strong>.</p>
            <p>Este es un correo de prueba enviado desde Wolk Nexus usando Hostinger SMTP.</p>
            <p style="margin:24px 0;">
                <a href="' . e($testUrl) . '" style="display:inline-block;background:#0ea5a8;color:#ffffff;padding:13px 18px;border-radius:12px;text-decoration:none;font-weight:bold;">
                    Abrir Nexus
                </a>
            </p>
            <p style="font-size:13px;color:#607084;word-break:break-all;">' . e($testUrl) . '</p>
        '
    );

    $plain = "Hola Naz,\n\nEste es un correo de prueba enviado desde Wolk Nexus usando Hostinger SMTP.\n\n{$testUrl}";

    $headers = '';
    $headers .= 'To: <' . $testTo . '>' . "\r\n";
    $headers .= 'Subject: ' . $subject . "\r\n";
    $headers .= 'From: ' . encode_mail_header($fromName) . ' <' . $fromEmail . '>' . "\r\n";
    $headers .= 'Reply-To: ' . encode_mail_header($replyToName) . ' <' . $replyToEmail . '>' . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n\r\n";

    $message = '';
    $message .= '--' . $boundary . "\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $plain . "\r\n\r\n";
    $message .= '--' . $boundary . "\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $html . "\r\n\r\n";
    $message .= '--' . $boundary . "--\r\n";

    fwrite($socket, $headers . $message . "\r\n.\r\n");

    $response = read_smtp_response($socket);

    if (!str_starts_with($response, '250')) {
        throw new RuntimeException('El servidor no aceptó el mensaje: ' . $response);
    }

    add_step($steps, 'Envío del mensaje', 'ok', $response);

    send_smtp_command($socket, 'QUIT');
    fclose($socket);

    $mailSent = true;
} catch (Throwable $exception) {
    $error = $exception->getMessage();

    if (isset($socket) && is_resource($socket)) {
        fclose($socket);
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Prueba de correo | Wolk Nexus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: #f4f7fa;
            color: #0f172a;
            padding: 24px;
        }

        .card {
            width: min(720px, 100%);
            background: #ffffff;
            border: 1px solid #dbe5ec;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }

        .ok,
        .error {
            padding: 14px 16px;
            border-radius: 14px;
            margin: 18px 0;
            font-weight: 700;
        }

        .ok {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .step {
            border: 1px solid #dbe5ec;
            border-radius: 14px;
            padding: 12px;
            margin-top: 10px;
            background: #f8fafc;
        }

        .step strong {
            display: block;
            margin-bottom: 6px;
        }

        code {
            display: block;
            background: #eef2f7;
            border-radius: 10px;
            padding: 10px;
            word-break: break-all;
            white-space: pre-wrap;
            font-size: 12px;
        }

        p {
            line-height: 1.6;
            color: #607084;
        }
    </style>
</head>

<body>
    <main class="card">
        <h1>Prueba de correo Nexus</h1>

        <?php if ($mailSent): ?>
            <div class="ok">
                Correo enviado correctamente.
            </div>

            <p>Revisa la bandeja de entrada de:</p>
            <code><?= e($testTo) ?></code>
        <?php else: ?>
            <div class="error">
                No se pudo enviar el correo.
            </div>

            <p>Detalle real del error:</p>
            <code><?= e($error) ?></code>
        <?php endif; ?>

        <h2>Diagnóstico SMTP</h2>

        <?php foreach ($steps as $step): ?>
            <div class="step">
                <strong><?= e($step['label']) ?> — <?= e(strtoupper($step['status'])) ?></strong>
                <code><?= e($step['detail']) ?></code>
            </div>
        <?php endforeach; ?>

        <p>Servidor configurado:</p>
        <code><?= e((string) mail_config('host')) ?>:<?= e((string) mail_config('port')) ?> / <?= e((string) mail_config('encryption')) ?></code>

        <p>Remitente configurado:</p>
        <code><?= e((string) mail_config('from_email')) ?></code>

        <p>Cuando termine la prueba, elimina este archivo:</p>
        <code>test-mail.php</code>
    </main>
</body>

</html>