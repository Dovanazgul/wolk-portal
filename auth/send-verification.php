<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/mail.php';

require_auth();

$currentUser = auth_user();

if (!$currentUser) {
    redirect('auth/login.php');
}

$systemName = app_visible_name();

$pageTitle = 'Enviar verificación | ' . $systemName;
$pageDescription = 'Envío de correo de verificación para ' . $systemName . '.';

$error = '';
$success = '';

if (!empty($currentUser['email_verified_at'])) {
    $success = 'Tu correo corporativo ya está verificado.';
} else {
    try {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+60 minutes'));

        db()->beginTransaction();

        $deleteOldTokens = db()->prepare("
            DELETE FROM email_verification_tokens
            WHERE user_id = :user_id
        ");

        $deleteOldTokens->execute([
            ':user_id' => $currentUser['id'],
        ]);

        $insertToken = db()->prepare("
            INSERT INTO email_verification_tokens (
                user_id,
                email,
                token_hash,
                expires_at,
                ip_address
            ) VALUES (
                :user_id,
                :email,
                :token_hash,
                :expires_at,
                :ip_address
            )
        ");

        $insertToken->execute([
            ':user_id' => $currentUser['id'],
            ':email' => $currentUser['email'],
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        db()->commit();

        $verificationUrl = app_url('auth/verify-email.php?token=' . urlencode($token));

        $mailSent = nexus_send_mail(
            (string) $currentUser['email'],
            'Verifica tu correo | ' . $systemName,
            email_verification_html((string) $currentUser['full_name'], $verificationUrl),
            email_verification_text((string) $currentUser['full_name'], $verificationUrl)
        );

        if (!$mailSent) {
            $cleanup = db()->prepare("
                DELETE FROM email_verification_tokens
                WHERE user_id = :user_id
            ");

            $cleanup->execute([
                ':user_id' => $currentUser['id'],
            ]);

            $error = 'No se pudo enviar el correo de verificación. Revisa la configuración SMTP del Portal interno.';
        } else {
            $success = 'Se envió un correo de verificación a tu cuenta corporativa.';
        }
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        $error = 'No se pudo generar la verificación de correo.';
    }
}

require_once __DIR__ . '/../includes/head.php';

?>

<main class="auth-page">
    <section class="auth-card">
        <div class="brand" style="margin-bottom:22px;">
            <img
                class="brand__img"
                src="<?= e(asset_url('img/wolk_it_services_logo.jpeg')) ?>"
                alt="WOLK-IT">

            <span class="brand__text">
                <strong class="brand__name">WOLK-IT</strong>
                <span class="brand__sub">Portal interno</span>
                <span class="brand__tag">Verificación de correo</span>
            </span>
        </div>

        <h1>Verificación de correo</h1>

        <p>
            El Portal interno enviará un enlace de verificación a tu correo corporativo.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert--danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert" style="background:#d1e7dd;color:#0f5132;border:1px solid #badbcc;">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('profile')) ?>">
                Ir a mi perfil
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('/')) ?>">
                Volver al inicio
            </a>
        </div>
    </section>
</main>

</body>

</html>