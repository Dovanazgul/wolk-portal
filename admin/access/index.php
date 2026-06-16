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
$canViewAccess = auth_can_view_access();

$pageTitle = 'Accesos | ' . $systemName;
$pageDescription = 'Administración de accesos del Portal interno.';

$roles = [];
$areas = [];
$departments = [];

if ($canViewAccess) {
    $roles = db()->query("
        SELECT
            r.id,
            r.name,
            r.slug,
            COUNT(DISTINCT ur.user_id) AS total_users
        FROM roles r
        LEFT JOIN user_roles ur ON ur.role_id = r.id
        GROUP BY
            r.id,
            r.name,
            r.slug
        ORDER BY r.name ASC
    ")->fetchAll();

    $areas = db()->query("
        SELECT
            a.id,
            a.name,
            a.slug,
            COUNT(DISTINCT u.id) AS total_users
        FROM areas a
        LEFT JOIN users u ON u.area_id = a.id
        GROUP BY
            a.id,
            a.name,
            a.slug
        ORDER BY a.name ASC
    ")->fetchAll();

    $departments = db()->query("
        SELECT
            d.id,
            d.name,
            d.slug,
            COUNT(DISTINCT u.id) AS total_primary_users,
            COUNT(DISTINCT ud.user_id) AS total_related_users
        FROM departments d
        LEFT JOIN users u ON u.primary_department_id = d.id
        LEFT JOIN user_departments ud ON ud.department_id = d.id
        GROUP BY
            d.id,
            d.name,
            d.slug
        ORDER BY d.name ASC
    ")->fetchAll();
}

require_once __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<?php if (!$canViewAccess): ?>
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

        <h1>Accesos</h1>

        <p>
            Revisa cómo se organizan los permisos del Portal interno por rol, área y departamento.
        </p>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('admin')) ?>">
                Volver al panel
            </a>
        </div>
    </section>

    <section class="top-grid">
        <article class="card welcome-card">
            <h1>Rol</h1>

            <p>
                Define el nivel de permiso general del usuario.
            </p>

            <div class="newsletter-meta">
                <span class="tag tag--info">SUPERADMIN</span>
                <span class="tag tag--info">ADMIN</span>
                <span class="tag tag--info">DIRECCION</span>
            </div>
        </article>

        <article class="card welcome-card">
            <h1>Área y departamento</h1>

            <p>
                Definen la visibilidad operativa de documentos, solicitudes y módulos internos.
            </p>

            <div class="newsletter-meta">
                <span class="tag tag--ok">Área</span>
                <span class="tag tag--ok">Departamento</span>
            </div>
        </article>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Reglas principales</div>
                <h2>Matriz general de acceso</h2>
                <p>
                    Esta matriz sirve como base para controlar la visibilidad de módulos futuros.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <div class="service-item">
                        <span class="service-ico service-ico--green" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M32 10l18 8v12c0 13-8 21-18 24-10-3-18-11-18-24V18z"></path>
                                <path d="M24 32l5 5 12-14"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>SUPERADMIN</strong>
                            <span>Acceso completo a módulos, usuarios, roles, áreas, departamentos y configuraciones críticas.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--ok">Completo</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico service-ico--blue" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="20"></circle>
                                <path d="M32 18v14l10 6"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>CISO / CTO</strong>
                            <span>Perfiles tratados como SUPERADMIN para control completo del Portal interno.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--ok">Crítico</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M16 14h32v36H16z"></path>
                                <path d="M24 24h18"></path>
                                <path d="M24 32h18"></path>
                                <path d="M24 40h10"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>ADMIN / CEO</strong>
                            <span>Acceso administrativo amplio, sin configuraciones críticas reservadas para SUPERADMIN.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--info">Administrativo</span>
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
                            <strong>DIRECCION</strong>
                            <span>Acceso amplio a información interna, con excepción de configuraciones críticas.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--warning">Dirección</span>
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
                            <strong>OPERACIONES / COMERCIAL / ADMINISTRACION</strong>
                            <span>Acceso controlado por área y departamento para módulos, documentos y solicitudes relacionadas.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--info">Operativo</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="top-grid">
        <article class="card welcome-card">
            <h1>Roles registrados</h1>

            <p>
                Roles configurados y cantidad de usuarios asociados.
            </p>

            <div class="newsletter-meta">
                <?php foreach ($roles as $role): ?>
                    <span class="tag tag--info">
                        <?= e(strtoupper((string) $role['slug'])) ?> · <?= e((string) $role['total_users']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card welcome-card">
            <h1>Áreas registradas</h1>

            <p>
                Áreas configuradas y cantidad de usuarios principales.
            </p>

            <div class="newsletter-meta">
                <?php foreach ($areas as $area): ?>
                    <span class="tag tag--ok">
                        <?= e((string) $area['name']) ?> · <?= e((string) $area['total_users']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Departamentos</div>
                <h2>Visibilidad por departamento</h2>
                <p>
                    Los departamentos permiten separar accesos por operación, administración, dirección y comercial.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <?php foreach ($departments as $department): ?>
                        <div class="service-item">
                            <span class="service-ico service-ico--blue" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <rect x="12" y="14" width="40" height="38" rx="4"></rect>
                                    <path d="M22 24h20"></path>
                                    <path d="M22 32h20"></path>
                                    <path d="M22 40h12"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong><?= e((string) $department['name']) ?></strong>
                                <span><?= e((string) $department['slug']) ?></span>
                            </span>

                            <span class="service-right">
                                <span class="tag tag--info">
                                    <?= e((string) $department['total_primary_users']) ?> principal(es)
                                </span>

                                <span class="tag tag--ok">
                                    <?= e((string) $department['total_related_users']) ?> relacionado(s)
                                </span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
