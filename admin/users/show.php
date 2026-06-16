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
$userId = (int) ($_GET['id'] ?? 0);

$pageTitle = 'Detalle de usuario | ' . $systemName;
$pageDescription = 'Detalle de cuenta interna del Portal interno.';

$user = null;
$roles = [];
$departments = [];

if ($canManageUsers && $userId > 0) {
    $statement = db()->prepare("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.email_verified_at,
            u.position_name,
            u.status,
            u.must_change_password,
            u.password_changed_at,
            u.profile_confirmed_at,
            u.profile_photo_url,
            u.google_workspace_id,
            u.google_photo_synced_at,
            u.login_provider,
            u.last_login_at,
            u.created_at,
            u.updated_at,
            a.name AS area_name,
            d.name AS department_name
        FROM users u
        LEFT JOIN areas a ON a.id = u.area_id
        LEFT JOIN departments d ON d.id = u.primary_department_id
        WHERE u.id = :id
        LIMIT 1
    ");

    $statement->execute([
        ':id' => $userId,
    ]);

    $user = $statement->fetch() ?: null;

    if ($user) {
        $rolesStatement = db()->prepare("
            SELECT
                r.name,
                r.slug
            FROM user_roles ur
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
            ORDER BY r.name ASC
        ");

        $rolesStatement->execute([
            ':user_id' => $userId,
        ]);

        $roles = $rolesStatement->fetchAll();

        $departmentsStatement = db()->prepare("
            SELECT
                d.name,
                d.slug,
                ud.is_primary
            FROM user_departments ud
            INNER JOIN departments d ON d.id = ud.department_id
            WHERE ud.user_id = :user_id
            ORDER BY ud.is_primary DESC, d.name ASC
        ");

        $departmentsStatement->execute([
            ':user_id' => $userId,
        ]);

        $departments = $departmentsStatement->fetchAll();
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
<?php else: ?>
    <?php
    $emailVerified = !empty($user['email_verified_at']);
    $profileConfirmed = !empty($user['profile_confirmed_at']);
    $mustChangePassword = (int) ($user['must_change_password'] ?? 0) === 1;
    $isActive = (string) $user['status'] === 'activo';
    $lastLogin = !empty($user['last_login_at'])
        ? date('d/m/Y H:i', strtotime((string) $user['last_login_at']))
        : 'Sin registro';
    $createdAt = !empty($user['created_at'])
        ? date('d/m/Y H:i', strtotime((string) $user['created_at']))
        : 'Sin registro';
    $updatedAt = !empty($user['updated_at'])
        ? date('d/m/Y H:i', strtotime((string) $user['updated_at']))
        : 'Sin registro';
    ?>

    <section class="intro-block">
        <div class="eyebrow">Administración de usuarios</div>

        <h1><?= e((string) $user['full_name']) ?></h1>

        <p>
            Consulta el detalle de la cuenta, sus accesos, estado de seguridad y relación con área y departamento.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('admin/users/edit.php?id=' . (int) $user['id'])) ?>">
                Editar usuario
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('admin/users/send-reset.php?id=' . (int) $user['id'])) ?>">
                Enviar recuperación
            </a>

            <?php if (!$emailVerified): ?>
                <a class="btn btn--ghost" href="<?= e(base_url('admin/users/send-verification.php?id=' . (int) $user['id'])) ?>">
                    Enviar verificación
                </a>
            <?php endif; ?>

            <a class="btn btn--ghost" href="<?= e(base_url('admin/users/toggle-status.php?id=' . (int) $user['id'])) ?>">
                <?= $isActive ? 'Desactivar usuario' : 'Activar usuario' ?>
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('admin/users')) ?>">
                Volver a usuarios
            </a>
        </div>
    </section>

    <section class="top-grid">
        <article class="card welcome-card">
            <div class="brand" style="margin-bottom:18px;">
                <img
                    class="brand__img"
                    src="<?= e((string) ($user['profile_photo_url'] ?: asset_url('img/wolk_it_services_logo.jpeg'))) ?>"
                    alt="Perfil">

                <span class="brand__text">
                    <strong class="brand__name"><?= e((string) $user['full_name']) ?></strong>
                    <span class="brand__sub"><?= e((string) $user['position_name']) ?></span>
                    <span class="brand__tag"><?= $isActive ? 'Usuario activo' : 'Usuario inactivo' ?></span>
                </span>
            </div>

            <p>
                Cuenta registrada dentro del Portal interno.
            </p>

            <div class="newsletter-meta">
                <?php if ($isActive): ?>
                    <span class="tag tag--ok">Activo</span>
                <?php else: ?>
                    <span class="tag tag--warning"><?= e(strtoupper((string) $user['status'])) ?></span>
                <?php endif; ?>

                <?php if ($emailVerified): ?>
                    <span class="tag tag--ok">Correo verificado</span>
                <?php else: ?>
                    <span class="tag tag--warning">Correo pendiente</span>
                <?php endif; ?>

                <?php if ($profileConfirmed): ?>
                    <span class="tag tag--ok">Perfil confirmado</span>
                <?php else: ?>
                    <span class="tag tag--warning">Perfil pendiente</span>
                <?php endif; ?>
            </div>
        </article>

        <article class="card welcome-card">
            <h1>Seguridad</h1>

            <p>
                Estado de contraseña, verificación y último acceso de la cuenta.
            </p>

            <div class="newsletter-meta">
                <?php if ($mustChangePassword): ?>
                    <span class="tag tag--warning">Debe cambiar contraseña</span>
                <?php else: ?>
                    <span class="tag tag--ok">Contraseña actualizada</span>
                <?php endif; ?>

                <span class="tag tag--info">
                    Último acceso: <?= e($lastLogin) ?>
                </span>
            </div>

            <div class="hero-actions">
                <a class="btn btn--primary" href="<?= e(base_url('admin/users/send-reset.php?id=' . (int) $user['id'])) ?>">
                    Enviar enlace de recuperación
                </a>

                <?php if (!$emailVerified): ?>
                    <a class="btn btn--ghost" href="<?= e(base_url('admin/users/send-verification.php?id=' . (int) $user['id'])) ?>">
                        Enviar verificación de correo
                    </a>
                <?php endif; ?>

                <a class="btn btn--ghost" href="<?= e(base_url('admin/users/toggle-status.php?id=' . (int) $user['id'])) ?>">
                    <?= $isActive ? 'Desactivar usuario' : 'Activar usuario' ?>
                </a>
            </div>
        </article>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Datos principales</div>
                <h2>Información de la cuenta</h2>
                <p>
                    Datos registrados para el control de acceso del Portal interno.
                </p>
            </div>
        </div>

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
                            <strong>Nombre completo</strong>
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
                            <strong>Correo electrónico</strong>
                            <span><?= e((string) $user['email']) ?></span>
                        </span>

                        <span class="service-right">
                            <?php if ($emailVerified): ?>
                                <span class="tag tag--ok">Verificado</span>
                            <?php else: ?>
                                <span class="tag tag--warning">Pendiente</span>
                                <a class="tag tag--info" href="<?= e(base_url('admin/users/send-verification.php?id=' . (int) $user['id'])) ?>">
                                    Enviar verificación
                                </a>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M18 16h28v36H18z"></path>
                                <path d="M24 24h16"></path>
                                <path d="M24 32h16"></path>
                                <path d="M24 40h10"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Puesto</strong>
                            <span><?= e((string) $user['position_name']) ?></span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico service-ico--blue" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M12 50h40"></path>
                                <path d="M18 50V24h12v26"></path>
                                <path d="M34 50V14h12v36"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Área</strong>
                            <span><?= e((string) $user['area_name']) ?></span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico service-ico--green" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <rect x="12" y="14" width="40" height="38" rx="4"></rect>
                                <path d="M22 24h20"></path>
                                <path d="M22 32h20"></path>
                                <path d="M22 40h12"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Departamento principal</strong>
                            <span><?= e((string) $user['department_name']) ?></span>
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
                            <strong>Fechas de control</strong>
                            <span>Creado: <?= e($createdAt) ?></span>
                            <span>Actualizado: <?= e($updatedAt) ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="top-grid">
        <article class="card welcome-card">
            <h1>Roles</h1>

            <p>
                Roles asignados a la cuenta.
            </p>

            <div class="newsletter-meta">
                <?php if (!$roles): ?>
                    <span class="tag tag--warning">Sin roles</span>
                <?php endif; ?>

                <?php foreach ($roles as $role): ?>
                    <span class="tag tag--info">
                        <?= e(strtoupper((string) $role['slug'])) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card welcome-card">
            <h1>Departamentos</h1>

            <p>
                Departamentos relacionados con la cuenta.
            </p>

            <div class="newsletter-meta">
                <?php if (!$departments): ?>
                    <span class="tag tag--warning">Sin departamentos adicionales</span>
                <?php endif; ?>

                <?php foreach ($departments as $department): ?>
                    <span class="tag <?= (int) $department['is_primary'] === 1 ? 'tag--ok' : 'tag--info' ?>">
                        <?= e((string) $department['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
