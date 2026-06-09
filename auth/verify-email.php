<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$systemName = app_visible_name();

$pageTitle = 'Verificar correo | ' . $systemName;
$pageDescription = 'Verificación de correo corporativo para ' . $systemName . '.';

$error = '';
$success = '';

$token = trim((string) ($_GET['token'] ?? ''));

function find_email_verification_token(string $token): array|null
{
    if ($token === '') {
        return null;
    }

    $tokenHash = hash('sha256', $token);

    $statement = db()->prepare("
        SELECT
            evt.id AS token_id,
            evt.user_id,
            evt.email,
            evt.expires_at,
            evt.used_at,
            u.full_name,
            u.status
        FROM email_verification_tokens evt
        INNER JOIN users u ON u.id = evt.user_id
        WHERE evt.token_hash = :token_hash
        AND evt.used_at IS NULL
        AND evt.expires_at >= NOW()
        AND u.status = 'activo'
        LIMIT 1
    ");

    $statement->execute([
        ':token_hash' => $tokenHash,
    ]);

    $record = $statement->fetch();

    return $record ?: null;
}

$verificationRecord = find_email_verification_token($token);

if ($token === '' || !$verificationRecord) {
    $error = 'El enlace de verificación no es válido o ya venció.';
} else {
    try {
        db()->beginTransaction();

        $updateUser = db()->prepare("
            UPDATE users
            SET email_verified_at = NOW()
            WHERE id = :id
        ");

        $updateUser->execute([
            ':id' => $verificationRecord['user_id'],
        ]);

        $markToken = db()->prepare("
            UPDATE email_verification_tokens
            SET used_at = NOW()
            WHERE id = :id
        ");

        $markToken->execute([
            ':id' => $verificationRecord['token_id'],
        ]);

        $deleteOtherTokens = db()->prepare("
            DELETE FROM email_verification_tokens
            WHERE user_id = :user_id
            AND id <> :token_id
        ");

        $deleteOtherTokens->execute([
            ':user_id' => $verificationRecord['user_id'],
            ':token_id' => $verificationRecord['token_id'],
        ]);

        db()->commit();

        $success = 'Tu correo corporativo fue verificado correctamente.';
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        $error = 'No se pudo verificar el correo. Inténtalo nuevamente.';
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

        <h1>Verificar correo</h1>

        <p>
            El Portal interno valida tu correo corporativo para confirmar tu cuenta de acceso.
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
            <?php if (auth_check()): ?>
                <a class="btn btn--primary" href="<?= e(base_url('/')) ?>">
                    Ir al Portal interno
                </a>

                <a class="btn btn--ghost" href="<?= e(base_url('profile')) ?>">
                    Mi perfil
                </a>
            <?php else: ?>
                <a class="btn btn--primary" href="<?= e(base_url('auth/login.php')) ?>">
                    Ir al login
                </a>
            <?php endif; ?>

            <a class="btn btn--ghost" href="<?= e(base_url('/')) ?>">
                Volver al inicio
            </a>
        </div>
    </section>
</main>

</body>

</html>