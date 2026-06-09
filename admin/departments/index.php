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

$pageTitle = 'Departamentos | ' . $systemName;
$pageDescription = 'Administración de departamentos del Portal interno.';

$departments = [];

if ($canViewStructure) {
    $statement = db()->query("
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
    ");

    $departments = $statement->fetchAll();
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

        <h1>Departamentos</h1>

        <p>
            Consulta los departamentos configurados y su relación con los usuarios del Portal interno.
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
                <div class="eyebrow">Estructura de accesos</div>
                <h2>Departamentos disponibles</h2>
                <p>
                    Los departamentos influyen en la visibilidad de documentos, solicitudes y módulos internos.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <?php if (!$departments): ?>
                        <div class="service-item">
                            <span class="service-copy">
                                <strong>Sin departamentos registrados</strong>
                                <span>No se encontraron departamentos para mostrar.</span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($departments as $department): ?>
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

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Regla de acceso</div>
                <h2>Uso de departamentos</h2>
                <p>
                    Cada usuario tiene un departamento principal y puede tener relación con otros departamentos según sus responsabilidades.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Departamento principal</strong>
                            <span>Define la pertenencia principal del usuario dentro de la estructura interna.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Departamentos relacionados</strong>
                            <span>Permiten ampliar visibilidad cuando un usuario participa en más de una operación interna.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Accesos por visibilidad</strong>
                            <span>Los módulos futuros podrán validar rol, área y departamento antes de mostrar información.</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
