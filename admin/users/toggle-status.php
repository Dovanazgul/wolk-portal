<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

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

$pageTitle = 'Cambiar estado | ' . $systemName;
$pageDescription = 'Cambio de estado de usuario dentro del Portal interno.';

$error = '';
$user = null;
$targetRoleSlugs = [];
$targetIsProtected = false;

if ($canManageUsers && $userId > 0) {
    $statement = db()->prepare("
        SELECT
            id,
            full_name,
            email,
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

    if ((int) $user['id'] === (int) $currentUser['id']) {
        $error = 'No puedes desactivar tu propia cuenta.';
    } elseif (!$isSuperadmin && $targetIsProtected) {
        $error = 'No tienes permiso para cambiar el estado de una cuenta crítica.';
    } else {
        $currentStatus = (string) $user['status'];
        $newStatus = $currentStatus === 'activo' ? 'inactivo' : 'activo';

        try {
            $updateStatement = db()->prepare("
                UPDATE users
                SET
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
                LIMIT 1
            ");

            $updateStatement->execute([
                ':status' => $newStatus,
                ':id' => $user['id'],
            ]);

            $checkStatement = db()->prepare("
                SELECT status
                FROM users
                WHERE id = :id
                LIMIT 1
            ");

            $checkStatement->execute([
                ':id' => $user['id'],
            ]);

            $savedStatus = (string) $checkStatement->fetchColumn();

            if ($savedStatus !== $newStatus) {
                $error = 'El estado no se actualizó en la base de datos.';
            } else {
                redirect('admin/users/show.php?id=' . (int) $user['id']);
            }
        } catch (Throwable $exception) {
            $error = 'No se pudo cambiar el estado del usuario.';
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

        <h1>No puedes cambiar esta cuenta</h1>

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
    <?php
    $isActive = (string) $user['status'] === 'activo';
    $nextAction = $isActive ? 'desactivar' : 'activar';
    ?>

    <section class="intro-block">
        <div class="eyebrow">Administración de usuarios</div>

        <h1>Cambiar estado de usuario</h1>

        <p>
            Esta acción cambia el acceso del usuario al Portal interno sin eliminar su información.
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
                <div class="eyebrow">Cambio de estado</div>
                <h2><?= e((string) $user['full_name']) ?></h2>
                <p>
                    Confirma si deseas <?= e($nextAction) ?> esta cuenta.
                </p>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert--danger">
                <?= e($error) ?>
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
                    </div>

                    <div class="service-item">
                        <span class="service-ico" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="20"></circle>
                                <path d="M32 18v14l10 6"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Estado actual</strong>
                            <span><?= e(ucfirst((string) $user['status'])) ?></span>
                        </span>

                        <span class="service-right">
                            <?php if ($isActive): ?>
                                <span class="tag tag--ok">Activo</span>
                            <?php else: ?>
                                <span class="tag tag--warning">Inactivo</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" style="margin-top:22px;">
            <?= csrf_field() ?>

            <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">

            <div class="hero-actions">
                <button class="btn btn--primary" type="submit">
                    <?= $isActive ? 'Desactivar usuario' : 'Activar usuario' ?>
                </button>

                <a class="btn btn--ghost" href="<?= e(base_url('admin/users/show.php?id=' . (int) $user['id'])) ?>">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
