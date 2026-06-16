<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/mail.php';

require_auth();
require_password_updated();
require_profile_confirmed();

$currentUser = auth_user();

if (!$currentUser) {
    redirect('auth/login.php');
}

$systemName = app_visible_name();
$canManageUsers = auth_can_manage_users();
$isSuperadmin = auth_is_superadmin();
$userId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$pageTitle = 'Enviar verificación | ' . $systemName;
$pageDescription = 'Envío de verificación de correo desde administración.';

$error = '';
$success = '';
$user = null;
$targetRoleSlugs = [];
$targetIsProtected = false;

if ($canManageUsers && $userId > 0) {
    $statement = db()->prepare("
        SELECT
            id,
            full_name,
            email,
            email_verified_at,
            status
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $statement->execute([
        ':id' => $userId,
    ]);

    $user = $statement->fetch() ?: null;

    if ($user) {
        $rolesStatement = db()->prepare("
            SELECT r.slug
            FROM user_roles ur
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
        ");

        $rolesStatement->execute([
            ':user_id' => $userId,
        ]);

        $targetRoleSlugs = array_map('strtolower', array_column($rolesStatement->fetchAll(), 'slug'));
        $targetIsProtected = count(array_intersect($targetRoleSlugs, ['superadmin', 'ciso', 'cto'])) > 0;
    }
}

if ($canManageUsers && $user && is_post()) {
    verify_csrf();

    if (!$isSuperadmin && $targetIsProtected) {
        $error = 'No tienes permiso para enviar verificación a una cuenta crítica.';
    } elseif ((string) $user['status'] !== 'activo') {
        $error = 'No se puede enviar verificación a un usuario inactivo.';
    } elseif (!empty($user['email_verified_at'])) {
        $success = 'El correo de este usuario ya está verificado.';
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
                ':user_id' => $user['id'],
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
                ':user_id' => $user['id'],
                ':email' => $user['email'],
                ':token_hash' => $tokenHash,
                ':expires_at' => $expiresAt,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            db()->commit();

            $verificationUrl = app_url('auth/verify-email.php?token=' . urlencode($token));

            $mailSent = nexus_send_mail(
                (string) $user['email'],
                'Verifica tu correo | ' . $systemName,
                email_verification_html((string) $user['full_name'], $verificationUrl),
                email_verification_text((string) $user['full_name'], $verificationUrl)
            );

            if (!$mailSent) {
                $cleanup = db()->prepare("
                    DELETE FROM email_verification_tokens
                    WHERE user_id = :user_id
                ");

                $cleanup->execute([
                    ':user_id' => $user['id'],
                ]);

                $error = 'No se pudo enviar el correo de verificación.';
            } else {
                $success = 'Se envió el enlace de verificación al correo del usuario.';
            }
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            $error = 'No se pudo generar el enlace de verificación.';
        }
    }
}

require_once __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<?php if (!$canManageUsers): ?>
    <section class="intro-block">
        <div class="eyebrow">Acceso restringido</div>

        <h1>No tienes permiso para entrar</h1>

        <p>
            Esta sección está reservada para usuarios con permisos administrativos.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('/')) ?>">
                Volver al inicio
            </a>
        </div>
    </section>
<?php elseif (!$user): ?>
    <section class="intro-block">
        <div class="eyebrow">Usuario no encontrado</div>

        <h1>No se encontró la cuenta</h1>

        <p>
            El usuario solicitado no existe o no está disponible.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('admin/users')) ?>">
                Volver a usuarios
            </a>
        </div>
    </section>
<?php elseif (!$isSuperadmin && $targetIsProtected): ?>
    <section class="intro-block">
        <div class="eyebrow">Acceso restringido</div>

        <h1>No puedes enviar verificación a esta cuenta</h1>

        <p>
            Esta cuenta tiene permisos críticos y solo puede ser administrada por SUPERADMIN, CISO o CTO.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('admin/users/show.php?id=' . (int) $user['id'])) ?>">
                Volver al detalle
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('admin/users')) ?>">
                Volver a usuarios
            </a>
        </div>
    </section>
<?php else: ?>
    <section class="intro-block">
        <div class="eyebrow">Administración de usuarios</div>

        <h1>Enviar verificación</h1>

        <p>
            Se enviará un enlace temporal para que el usuario confirme su correo electrónico.
        </p>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('admin/users/show.php?id=' . (int) $user['id'])) ?>">
                Volver al detalle
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('admin/users')) ?>">
                Volver a usuarios
            </a>
        </div>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Verificación de correo</div>
                <h2><?= e((string) $user['full_name']) ?></h2>
                <p>
                    El correo se enviará a la cuenta registrada del usuario.
                </p>
            </div>
        </div>

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

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <div class="service-item">
                        <span class="service-ico service-ico--blue" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="20" r="10"></circle>
                                <path d="M16 52c3-14 29-14 32 0"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Usuario</strong>
                            <span><?= e((string) $user['full_name']) ?></span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico service-ico--green" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M12 18h40v28H12z"></path>
                                <path d="M12 20l20 16 20-16"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Correo</strong>
                            <span><?= e((string) $user['email']) ?></span>
                        </span>

                        <span class="service-right">
                            <?php if (!empty($user['email_verified_at'])): ?>
                                <span class="tag tag--ok">Verificado</span>
                            <?php else: ?>
                                <span class="tag tag--warning">Pendiente</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="20"></circle>
                                <path d="M32 18v14l10 6"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Vigencia del enlace</strong>
                            <span>60 minutos</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success === '' && empty($user['email_verified_at'])): ?>
            <form method="POST" style="margin-top:22px;">
                <?= csrf_field() ?>

                <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">

                <div class="hero-actions">
                    <button class="btn btn--primary" type="submit">
                        Enviar enlace de verificación
                    </button>

                    <a class="btn btn--ghost" href="<?= e(base_url('admin/users/show.php?id=' . (int) $user['id'])) ?>">
                        Cancelar
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
