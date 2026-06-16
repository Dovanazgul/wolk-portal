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

$pageTitle = 'Usuarios | ' . $systemName;
$pageDescription = 'Administración de usuarios del Portal interno.';

$users = [];
$totalUsers = 0;
$totalActiveUsers = 0;

if ($canManageUsers) {
    $statement = db()->query("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.position_name,
            u.status,
            u.email_verified_at,
            u.profile_confirmed_at,
            u.last_login_at,
            a.name AS area_name,
            d.name AS department_name,
            GROUP_CONCAT(DISTINCT r.slug ORDER BY r.name SEPARATOR ', ') AS roles
        FROM users u
        LEFT JOIN areas a ON a.id = u.area_id
        LEFT JOIN departments d ON d.id = u.primary_department_id
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        GROUP BY
            u.id,
            u.full_name,
            u.email,
            u.position_name,
            u.status,
            u.email_verified_at,
            u.profile_confirmed_at,
            u.last_login_at,
            a.name,
            d.name
        ORDER BY u.full_name ASC
    ");

    $users = $statement->fetchAll();

    $totalUsers = count($users);
    $totalActiveUsers = count(array_filter($users, static function (array $user): bool {
        return (string) $user['status'] === 'activo';
    }));
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
<?php else: ?>
    <section class="intro-block">
        <div class="eyebrow">Administración</div>

        <h1>Usuarios</h1>

        <p>
            Consulta las cuentas registradas, sus roles, áreas, departamentos y estado dentro del Portal interno.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('admin/users/create')) ?>">
                Crear usuario
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('admin')) ?>">
                Volver al panel
            </a>
        </div>
    </section>

    <section class="top-grid">
        <article class="card welcome-card">
            <h1><?= e((string) $totalUsers) ?></h1>

            <p>
                Usuarios registrados en la base de datos.
            </p>

            <div class="newsletter-meta">
                <span class="tag tag--info">Total</span>
            </div>
        </article>

        <article class="card welcome-card">
            <h1><?= e((string) $totalActiveUsers) ?></h1>

            <p>
                Usuarios activos con acceso permitido.
            </p>

            <div class="newsletter-meta">
                <span class="tag tag--ok">Activos</span>
            </div>
        </article>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Cuentas internas</div>
                <h2>Listado de usuarios</h2>
                <p>
                    Revisa la información principal de cada cuenta registrada.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <?php if (!$users): ?>
                        <div class="service-item">
                            <span class="service-copy">
                                <strong>Sin usuarios registrados</strong>
                                <span>No se encontraron cuentas para mostrar.</span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($users as $user): ?>
                        <?php
                        $roles = trim((string) ($user['roles'] ?? ''));
                        $status = (string) ($user['status'] ?? '');
                        $isActive = $status === 'activo';
                        $emailVerified = !empty($user['email_verified_at']);
                        $profileConfirmed = !empty($user['profile_confirmed_at']);
                        $lastLogin = !empty($user['last_login_at'])
                            ? date('d/m/Y H:i', strtotime((string) $user['last_login_at']))
                            : 'Sin registro';
                        ?>

                        <a class="service-item" href="<?= e(base_url('admin/users/show.php?id=' . (int) $user['id'])) ?>">
                            <span class="service-ico service-ico--blue" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <circle cx="32" cy="20" r="10"></circle>
                                    <path d="M16 52c3-14 29-14 32 0"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong><?= e((string) $user['full_name']) ?></strong>
                                <span><?= e((string) $user['email']) ?></span>
                                <span>
                                    <?= e((string) $user['position_name']) ?>
                                    · <?= e((string) $user['area_name']) ?>
                                    · <?= e((string) $user['department_name']) ?>
                                </span>
                                <span>Último acceso: <?= e($lastLogin) ?></span>
                            </span>

                            <span class="service-right">
                                <?php if ($isActive): ?>
                                    <span class="tag tag--ok">Activo</span>
                                <?php else: ?>
                                    <span class="tag tag--warning"><?= e(strtoupper($status)) ?></span>
                                <?php endif; ?>

                                <?php if ($emailVerified): ?>
                                    <span class="tag tag--info">Correo verificado</span>
                                <?php else: ?>
                                    <span class="tag tag--warning">Correo pendiente</span>
                                <?php endif; ?>

                                <?php if ($profileConfirmed): ?>
                                    <span class="tag tag--ok">Perfil confirmado</span>
                                <?php else: ?>
                                    <span class="tag tag--warning">Perfil pendiente</span>
                                <?php endif; ?>

                                <?php if ($roles !== ''): ?>
                                    <span class="tag tag--info"><?= e(strtoupper($roles)) ?></span>
                                <?php endif; ?>

                                <span class="service-arrow">›</span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
