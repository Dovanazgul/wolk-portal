<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
require_password_updated();
require_profile_confirmed();

$currentUser = auth_user();

if (!$currentUser) {
    redirect('auth/login.php');
}

$systemName = app_visible_name();
$isAdmin = auth_is_admin();
$isSuperadmin = auth_is_superadmin();

$pageTitle = 'Administración | ' . $systemName;
$pageDescription = 'Panel administrativo del Portal interno.';

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<?php if (!$isAdmin): ?>
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

        <h1>Panel administrativo</h1>

        <p>
            Gestiona usuarios, accesos y estructura interna del Portal interno según tu nivel de permiso.
        </p>
    </section>

    <section class="top-grid">
        <article class="card welcome-card">
            <h1>Usuarios</h1>

            <p>
                Alta, consulta y administración de cuentas internas.
            </p>

            <div class="hero-actions">
                <a class="btn btn--primary" href="<?= e(base_url('admin/users')) ?>">
                    Administrar usuarios
                </a>
            </div>
        </article>

        <article class="card welcome-card">
            <h1>Accesos</h1>

            <p>
                Consulta la lógica general de acceso por rol, área y departamento.
            </p>

            <div class="hero-actions">
                <a class="btn btn--primary" href="<?= e(base_url('admin/access')) ?>">
                    Ver accesos
                </a>
            </div>
        </article>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Módulos administrativos</div>
                <h2>Herramientas disponibles</h2>
                <p>
                    Los accesos visibles dependen del rol asignado a tu cuenta.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <a class="service-item" href="<?= e(base_url('admin/users')) ?>">
                        <span class="service-ico service-ico--blue" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="24" cy="22" r="8"></circle>
                                <path d="M10 48c2-12 26-12 28 0"></path>
                                <circle cx="44" cy="24" r="6"></circle>
                                <path d="M38 46c2-8 15-8 17 0"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Usuarios</strong>
                            <span>Consulta, edición y control de cuentas internas.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--info">Cuentas</span>
                            <span class="service-arrow">›</span>
                        </span>
                    </a>

                    <a class="service-item" href="<?= e(base_url('admin/access')) ?>">
                        <span class="service-ico service-ico--green" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M32 10l18 8v12c0 13-8 21-18 24-10-3-18-11-18-24V18z"></path>
                                <path d="M24 32l5 5 12-14"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Accesos</strong>
                            <span>Consulta la matriz general de permisos por rol, área y departamento.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--ok">Permisos</span>
                            <span class="service-arrow">›</span>
                        </span>
                    </a>

                    <?php if ($isSuperadmin): ?>
                        <a class="service-item" href="<?= e(base_url('admin/roles')) ?>">
                            <span class="service-ico service-ico--green" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <path d="M32 10l18 8v12c0 13-8 21-18 24-10-3-18-11-18-24V18z"></path>
                                    <path d="M24 32l5 5 12-14"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong>Roles</strong>
                                <span>Consulta perfiles como SUPERADMIN, ADMIN, CISO, CTO y áreas operativas.</span>
                            </span>

                            <span class="service-right">
                                <span class="tag tag--ok">Crítico</span>
                                <span class="service-arrow">›</span>
                            </span>
                        </a>

                        <a class="service-item" href="<?= e(base_url('admin/areas')) ?>">
                            <span class="service-ico" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <path d="M12 50h40"></path>
                                    <path d="M18 50V24h12v26"></path>
                                    <path d="M34 50V14h12v36"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong>Áreas</strong>
                                <span>Consulta las áreas internas relacionadas con la operación de Wolk IT.</span>
                            </span>

                            <span class="service-right">
                                <span class="tag tag--info">Estructura</span>
                                <span class="service-arrow">›</span>
                            </span>
                        </a>

                        <a class="service-item" href="<?= e(base_url('admin/departments')) ?>">
                            <span class="service-ico service-ico--blue" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <rect x="12" y="14" width="40" height="38" rx="4"></rect>
                                    <path d="M22 24h20"></path>
                                    <path d="M22 32h20"></path>
                                    <path d="M22 40h12"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong>Departamentos</strong>
                                <span>Consulta departamentos que influyen en accesos y visibilidad.</span>
                            </span>

                            <span class="service-right">
                                <span class="tag tag--warning">Acceso</span>
                                <span class="service-arrow">›</span>
                            </span>
                        </a>

                        <a class="service-item" href="<?= e(base_url('admin/login-events')) ?>">
                            <span class="service-ico service-ico--green" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="20"></circle>
                                    <path d="M32 18v14l10 6"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong>Eventos de acceso</strong>
                                <span>Consulta inicios de sesión, cierres y acciones de seguridad.</span>
                            </span>

                            <span class="service-right">
                                <span class="tag tag--info">Auditoría</span>
                                <span class="service-arrow">›</span>
                            </span>
                        </a>

                        <a class="service-item" href="<?= e(base_url('admin/settings')) ?>">
                            <span class="service-ico service-ico--blue" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="8"></circle>
                                    <path d="M32 12v8"></path>
                                    <path d="M32 44v8"></path>
                                    <path d="M12 32h8"></path>
                                    <path d="M44 32h8"></path>
                                    <path d="M18 18l6 6"></path>
                                    <path d="M40 40l6 6"></path>
                                    <path d="M46 18l-6 6"></path>
                                    <path d="M24 40l-6 6"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong>Configuración</strong>
                                <span>Revisión de entorno, URLs y correo saliente del sistema.</span>
                            </span>

                            <span class="service-right">
                                <span class="tag tag--warning">Crítico</span>
                                <span class="service-arrow">›</span>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../includes/footer.php';
