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

$pageTitle = 'Áreas | ' . $systemName;
$pageDescription = 'Administración de áreas del Portal interno.';

$areas = [];

if ($canViewStructure) {
    $statement = db()->query("
        SELECT
            a.id,
            a.name,
            a.slug,
            COUNT(u.id) AS total_users
        FROM areas a
        LEFT JOIN users u ON u.area_id = a.id
        GROUP BY
            a.id,
            a.name,
            a.slug
        ORDER BY a.name ASC
    ");

    $areas = $statement->fetchAll();
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

        <h1>Áreas</h1>

        <p>
            Consulta las áreas internas configuradas y cuántos usuarios pertenecen a cada una.
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
                <div class="eyebrow">Estructura interna</div>
                <h2>Áreas disponibles</h2>
                <p>
                    Las áreas ayudan a clasificar usuarios y accesos dentro del Portal interno.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <?php if (!$areas): ?>
                        <div class="service-item">
                            <span class="service-copy">
                                <strong>Sin áreas registradas</strong>
                                <span>No se encontraron áreas para mostrar.</span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($areas as $area): ?>
                        <div class="service-item">
                            <span class="service-ico service-ico--blue" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <path d="M12 50h40"></path>
                                    <path d="M18 50V24h12v26"></path>
                                    <path d="M34 50V14h12v36"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong><?= e((string) $area['name']) ?></strong>
                                <span><?= e((string) $area['slug']) ?></span>
                            </span>

                            <span class="service-right">
                                <span class="tag tag--info">
                                    <?= e((string) $area['total_users']) ?> usuario(s)
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
