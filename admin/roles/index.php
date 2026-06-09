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
$canViewStructure = auth_can_view_structure();

$pageTitle = 'Roles | ' . $systemName;
$pageDescription = 'Administración de roles del Portal interno.';

$roles = [];

if ($canViewStructure) {
    $statement = db()->query("
        SELECT
            r.id,
            r.name,
            r.slug,
            COUNT(ur.user_id) AS total_users
        FROM roles r
        LEFT JOIN user_roles ur ON ur.role_id = r.id
        GROUP BY
            r.id,
            r.name,
            r.slug
        ORDER BY r.name ASC
    ");

    $roles = $statement->fetchAll();
}

require_once __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<?php if (!$canViewStructure): ?>
    <section class="intro-block">
        <div class="eyebrow">Acceso restringido</div>

        <h1>No tienes permiso para entrar</h1>

        <p>
            Esta sección está reservada para usuarios con permisos críticos.
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

        <h1>Roles</h1>

        <p>
            Consulta los roles configurados y cuántos usuarios tienen asignado cada perfil de acceso.
        </p>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('admin')) ?>">
                Volver al panel
            </a>
        </div>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Control de acceso</div>
                <h2>Roles disponibles</h2>
                <p>
                    Los roles ayudan a definir qué puede ver o administrar cada usuario dentro del Portal interno.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <?php if (!$roles): ?>
                        <div class="service-item">
                            <span class="service-copy">
                                <strong>Sin roles registrados</strong>
                                <span>No se encontraron roles para mostrar.</span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($roles as $role): ?>
                        <div class="service-item">
                            <span class="service-ico service-ico--green" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <path d="M32 10l18 8v12c0 13-8 21-18 24-10-3-18-11-18-24V18z"></path>
                                    <path d="M24 32l5 5 12-14"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong><?= e((string) $role['name']) ?></strong>
                                <span><?= e(strtoupper((string) $role['slug'])) ?></span>
                            </span>

                            <span class="service-right">
                                <span class="tag tag--info">
                                    <?= e((string) $role['total_users']) ?> usuario(s)
                                </span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Criterio de permisos</div>
                <h2>Reglas actuales</h2>
                <p>
                    Estas reglas se consideran para organizar el acceso por rol, área y departamento.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <div class="service-item">
                        <span class="service-copy">
                            <strong>SUPERADMIN</strong>
                            <span>Acceso completo a módulos, administración y configuraciones críticas.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>CISO / CTO</strong>
                            <span>Perfiles considerados como SUPERADMIN para control completo del sistema.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>ADMIN / CEO</strong>
                            <span>Acceso administrativo sin configuraciones críticas de SUPERADMIN.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>DIRECCION</strong>
                            <span>Acceso amplio a la información interna, excepto configuraciones críticas.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Áreas y departamentos</strong>
                            <span>También influyen en la visibilidad de documentos, solicitudes y módulos internos.</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
