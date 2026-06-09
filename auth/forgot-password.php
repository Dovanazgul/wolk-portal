<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/auth.php';

guest_only();

$systemName = app_visible_name();

$pageTitle = 'Recuperar contraseña | ' . $systemName;
$pageDescription = 'Recuperación de contraseña del Portal del empleado de Wolk IT.';

$error = '';
$success = '';

function forgot_read_smtp_response($socket): string
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

function forgot_send_smtp_command($socket, string $command): string
{
    fwrite($socket, $command . "\r\n");

    return forgot_read_smtp_response($socket);
}

function forgot_reset_email_html(string $toName, string $resetUrl, string $logoUrl): string
{
    $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
    $safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    

    return '<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de contraseña</title>
</head>
<body style="margin:0;padding:0;background:#eef4f7;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#eef4f7;margin:0;padding:34px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #dbe5ec;border-radius:24px;overflow:hidden;box-shadow:0 20px 48px rgba(15,23,42,0.10);">
                    <tr>
                        <td style="padding:26px 34px 22px;background:#ffffff;border-bottom:1px solid #e5edf3;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="width:58px;height:58px;border-radius:16px;background:#ffffff;border:1px solid #dbe5ec;text-align:center;vertical-align:middle;       overflow:hidden;">
                                                    <div style="font-size:13px;font-weight:900;color:#0f172a;line-height:58px;letter-spacing:-.4px;">
                                                        WOLK
                                                    </div>
                                                </td>
                                                <td style="padding-left:14px;vertical-align:middle;">
                                                    <div style="font-size:21px;font-weight:900;letter-spacing:.2px;color:#0f172a;line-height:1.1;">
                                                        WOLK-IT
                                                    </div>
                                                    <div style="font-size:12px;font-weight:700;color:#607084;text-transform:uppercase;letter-spacing:.7px;margin-top:3px;">
                                                        Portal del empleado
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;padding:8px 12px;border-radius:999px;background:#eefaf8;color:#0f766e;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.5px;">
                                            Seguridad
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0;background:linear-gradient(135deg,#0ea5a8 0%,#37b24d 100%);">
                            <div style="padding:34px 36px 32px;">
                                <div style="width:54px;height:4px;border-radius:999px;background:rgba(255,255,255,.72);margin-bottom:18px;"></div>

                                <h1 style="margin:0 0 12px;font-size:32px;line-height:1.12;color:#ffffff;font-weight:900;letter-spacing:-.5px;">
                                    Recuperación de contraseña
                                </h1>

                                <p style="margin:0;color:rgba(255,255,255,.92);font-size:16px;line-height:1.6;max-width:540px;">
                                    Recibimos una solicitud para restablecer el acceso a tu cuenta del Portal del empleado.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:36px;">
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.7;color:#334155;">
                                Hola, <strong style="color:#0f172a;">' . $safeName . '</strong>.
                            </p>

                            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#334155;">
                                Para continuar con el cambio de contraseña, utiliza el siguiente botón. Por seguridad, el enlace estará disponible durante <strong>30 minutos</strong>.
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:30px 0 28px;">
                                <tr>
                                    <td style="border-radius:14px;background:#0ea5a8;">
                                        <a href="' . $safeResetUrl . '" style="display:inline-block;padding:15px 24px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:900;border-radius:14px;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #dbe5ec;border-radius:18px;margin:0 0 22px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <div style="font-size:12px;font-weight:900;color:#0f766e;text-transform:uppercase;letter-spacing:.7px;margin-bottom:8px;">
                                            Enlace alternativo
                                        </div>

                                        <p style="margin:0;font-size:13px;line-height:1.6;color:#475569;word-break:break-all;">
                                            ' . $safeResetUrl . '
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:18px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <div style="font-size:13px;font-weight:900;color:#9a3412;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;">
                                            Aviso de seguridad
                                        </div>

                                        <p style="margin:0;font-size:14px;line-height:1.7;color:#9a3412;">
                                            Si no solicitaste este cambio, ignora este correo. Tu contraseña actual no será modificada.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 36px;background:#f8fafc;border-top:1px solid #dbe5ec;">
                            <p style="margin:0 0 6px;font-size:13px;color:#607084;line-height:1.6;">
                                Este mensaje fue enviado automáticamente por el Portal del empleado de Wolk IT.
                            </p>

                            <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                                No respondas este correo. Por seguridad, no compartas este enlace con nadie.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:18px 0 0;font-size:12px;color:#94a3b8;">
                    Wolk IT Services
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function forgot_send_reset_mail(string $toEmail, string $toName, string $resetUrl, string $logoUrl): void
{
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
        throw new RuntimeException('Falta configurar usuario o contraseña SMTP en config/mail.php.');
    }

    $remoteHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
    $socket = fsockopen($remoteHost, $port, $errno, $errstr, 25);

    if (!$socket) {
        throw new RuntimeException("No se pudo conectar al SMTP. Error {$errno}: {$errstr}");
    }

    try {
        stream_set_timeout($socket, 25);

        $response = forgot_read_smtp_response($socket);

        if (!str_starts_with($response, '220')) {
            throw new RuntimeException('Respuesta inicial SMTP inesperada: ' . $response);
        }

        $response = forgot_send_smtp_command($socket, 'EHLO localhost');

        if (!str_starts_with($response, '250')) {
            throw new RuntimeException('EHLO falló: ' . $response);
        }

        if ($encryption === 'tls' || $encryption === 'starttls') {
            $response = forgot_send_smtp_command($socket, 'STARTTLS');

            if (!str_starts_with($response, '220')) {
                throw new RuntimeException('STARTTLS falló: ' . $response);
            }

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No se pudo activar TLS.');
            }

            $response = forgot_send_smtp_command($socket, 'EHLO localhost');

            if (!str_starts_with($response, '250')) {
                throw new RuntimeException('EHLO después de TLS falló: ' . $response);
            }
        }

        $response = forgot_send_smtp_command($socket, 'AUTH LOGIN');

        if (!str_starts_with($response, '334')) {
            throw new RuntimeException('AUTH LOGIN falló: ' . $response);
        }

        $response = forgot_send_smtp_command($socket, base64_encode($username));

        if (!str_starts_with($response, '334')) {
            throw new RuntimeException('Usuario SMTP rechazado: ' . $response);
        }

        $response = forgot_send_smtp_command($socket, base64_encode($password));

        if (!str_starts_with($response, '235')) {
            throw new RuntimeException('Contraseña SMTP rechazada: ' . $response);
        }

        $response = forgot_send_smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>');

        if (!str_starts_with($response, '250')) {
            throw new RuntimeException('MAIL FROM rechazado: ' . $response);
        }

        $response = forgot_send_smtp_command($socket, 'RCPT TO:<' . $toEmail . '>');

        if (!str_starts_with($response, '250') && !str_starts_with($response, '251')) {
            throw new RuntimeException('Destinatario rechazado: ' . $response);
        }

        $response = forgot_send_smtp_command($socket, 'DATA');

        if (!str_starts_with($response, '354')) {
            throw new RuntimeException('DATA rechazado: ' . $response);
        }

        $subject = encode_mail_header('Recuperación de contraseña | Portal del empleado');
        $boundary = 'wolk_reset_' . bin2hex(random_bytes(8));

        $html = forgot_reset_email_html($toName, $resetUrl, $logoUrl);

        $plain = "Hola, {$toName}.\n\n"
            . "Recibimos una solicitud para restablecer la contraseña de tu cuenta en el Portal del empleado.\n\n"
            . "Abre este enlace para continuar:\n"
            . $resetUrl . "\n\n"
            . "Este enlace estará disponible durante 30 minutos.\n\n"
            . "Si no solicitaste este cambio, puedes ignorar este correo.\n\n"
            . "Wolk IT Services";

        $headers = '';
        $headers .= 'To: ' . encode_mail_header($toName) . ' <' . $toEmail . '>' . "\r\n";
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

        $response = forgot_read_smtp_response($socket);

        if (!str_starts_with($response, '250')) {
            throw new RuntimeException('El servidor no aceptó el mensaje: ' . $response);
        }

        forgot_send_smtp_command($socket, 'QUIT');
        fclose($socket);
    } catch (Throwable $exception) {
        if (is_resource($socket)) {
            fclose($socket);
        }

        throw $exception;
    }
}

if (is_post()) {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));

    if ($email === '') {
        $error = 'Ingresa tu correo electrónico.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo electrónico válido.';
    } else {
        $statement = db()->prepare("
            SELECT
                id,
                full_name,
                email,
                status
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $statement->execute([
            ':email' => $email,
        ]);

        $user = $statement->fetch();

        if ($user && $user['status'] === 'activo') {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $disableTokens = db()->prepare("
                UPDATE password_reset_tokens
                SET used_at = NOW()
                WHERE user_id = :user_id
                AND used_at IS NULL
            ");

            $disableTokens->execute([
                ':user_id' => $user['id'],
            ]);

            $insertToken = db()->prepare("
                INSERT INTO password_reset_tokens (
                    user_id,
                    email,
                    token_hash,
                    expires_at,
                    ip_address,
                    created_at
                ) VALUES (
                    :user_id,
                    :email,
                    :token_hash,
                    DATE_ADD(NOW(), INTERVAL 30 MINUTE),
                    :ip_address,
                    NOW()
                )
            ");

            $insertToken->execute([
                ':user_id' => $user['id'],
                ':email' => $user['email'],
                ':token_hash' => $tokenHash,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $resetUrl = app_url('auth/reset-password.php?token=' . urlencode($token));
            $logoUrl = app_url('assets/img/wolk_it_services_logo.jpeg');

            try {
                forgot_send_reset_mail(
                    (string) $user['email'],
                    (string) $user['full_name'],
                    $resetUrl,
                    $logoUrl
                );
            } catch (Throwable $exception) {
                $error = 'Error SMTP: ' . $exception->getMessage();
            }
        }

        if ($error === '') {
            $success = 'Si el correo está registrado y activo, recibirás un enlace para restablecer tu contraseña.';
        }
    }
}

require_once __DIR__ . '/../includes/head.php';

?>

<header class="topbar">
    <div class="wrap topbar__inner">
        <a class="brand" href="<?= e(base_url('/')) ?>">
            <img
                class="brand__img"
                src="<?= e(asset_url('img/wolk_it_services_logo.jpeg')) ?>"
                alt="WOLK-IT">

            <span class="brand__text">
                <strong class="brand__name">WOLK-IT</strong>
                <span class="brand__sub">Portal del empleado</span>
                <span class="brand__tag">Recuperación de acceso</span>
            </span>
        </a>

        <a class="login-link" href="<?= e(base_url('/')) ?>">
            Iniciar sesión
        </a>
    </div>
</header>

<main class="auth-page auth-page--employee">
    <section class="auth-card auth-card--center">
        <div class="brand" style="margin-bottom:22px;">
            <img
                class="brand__img"
                src="<?= e(asset_url('img/wolk_it_services_logo.jpeg')) ?>"
                alt="WOLK-IT">

            <span class="brand__text">
                <strong class="brand__name">WOLK-IT</strong>
                <span class="brand__sub">Portal del empleado</span>
                <span class="brand__tag">Recuperar contraseña</span>
            </span>
        </div>

        <h1>Recuperar contraseña</h1>

        <p>
            Ingresa tu correo autorizado. Te enviaremos un enlace para restablecer tu contraseña.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert--danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input
                    class="form-control"
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="correo@wolk-it.com"
                    required>
            </div>

            <button class="btn btn--primary" type="submit" style="width:100%;">
                Enviar enlace de recuperación
            </button>
        </form>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('/')) ?>">
                Volver al inicio de sesión
            </a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../includes/footer-content.php'; ?>
</div>
</body>

</html>