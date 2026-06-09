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
$canViewSecurityEvents = auth_can_view_security_events();

$pageTitle = 'Eventos de acceso | ' . $systemName;
$pageDescription = 'Auditoría de accesos del Portal interno.';

$events = [];
$totalEvents = 0;

$eventLabels = [
    'login_success' => 'Inicio de sesión correcto',
    'login_failed' => 'Inicio de sesión fallido',
    'logout' => 'Cierre de sesión',
    'password_reset_requested' => 'Recuperación solicitada',
    'password_reset_completed' => 'Contraseña recuperada',
    'password_changed' => 'Contraseña cambiada',
    'profile_confirmed' => 'Perfil confirmado',
];

if ($canViewSecurityEvents) {
    $statement = db()->query("
        SELECT
            le.id,
            le.user_id,
            le.email,
            le.event_type,
            le.ip_address,
            le.user_agent,
            le.created_at,
            u.full_name
        FROM login_events le
        LEFT JOIN users u ON u.id = le.user_id
        ORDER BY le.created_at DESC
        LIMIT 100
    ");

    $events = $statement->fetchAll();
    $totalEvents = count($events);
}

require_once __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<?php if (!$canViewSecurityEvents): ?>
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
        <div class="eyebrow">Auditoría</div>

        <h1>Eventos de acceso</h1>

        <p>
            Consulta los últimos movimientos relacionados con inicio de sesión, cierre de sesión,
            cambio de contraseña y recuperación de acceso.
        </p>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('admin')) ?>">
                Volver al panel
            </a>
        </div>
    </section>

    <section class="top-grid">
        <article class="card welcome-card">
            <h1><?= e((string) $totalEvents) ?></h1>

            <p>
                Eventos recientes registrados.
            </p>

            <div class="newsletter-meta">
                <span class="tag tag--info">Últimos 100</span>
            </div>
        </article>

        <article class="card welcome-card">
            <h1>Auditoría</h1>

            <p>
                Esta información ayuda a revisar actividad de acceso y acciones de seguridad.
            </p>

            <div class="newsletter-meta">
                <span class="tag tag--ok">Seguridad</span>
                <span class="tag tag--info">Accesos</span>
            </div>
        </article>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Registro de actividad</div>
                <h2>Últimos eventos</h2>
                <p>
                    Se muestran los eventos más recientes del Portal interno.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <?php if (!$events): ?>
                        <div class="service-item">
                            <span class="service-copy">
                                <strong>Sin eventos registrados</strong>
                                <span>No se encontraron eventos de acceso para mostrar.</span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($events as $event): ?>
                        <?php
                        $eventType = (string) $event['event_type'];
                        $eventLabel = $eventLabels[$eventType] ?? $eventType;
                        $eventDate = !empty($event['created_at'])
                            ? date('d/m/Y H:i', strtotime((string) $event['created_at']))
                            : 'Sin fecha';

                        $tagClass = match ($eventType) {
                            'login_success',
                            'logout',
                            'password_changed',
                            'password_reset_completed',
                            'profile_confirmed' => 'tag--ok',
                            'login_failed' => 'tag--warning',
                            default => 'tag--info',
                        };

                        $displayName = trim((string) ($event['full_name'] ?? ''));

                        if ($displayName === '') {
                            $displayName = trim((string) ($event['email'] ?? 'Usuario no identificado'));
                        }
                        ?>

                        <div class="service-item">
                            <span class="service-ico service-ico--blue" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="20"></circle>
                                    <path d="M32 18v14l10 6"></path>
                                </svg>
                            </span>

                            <span class="service-copy">
                                <strong><?= e($eventLabel) ?></strong>
                                <span><?= e($displayName) ?></span>
                                <span><?= e((string) ($event['email'] ?? 'Sin correo')) ?></span>
                                <span>IP: <?= e((string) ($event['ip_address'] ?? 'Sin registro')) ?></span>
                                <span><?= e($eventDate) ?></span>
                            </span>

                            <span class="service-right">
                                <span class="tag <?= e($tagClass) ?>">
                                    <?= e(strtoupper($eventType)) ?>
                                </span>

                                <span class="tag tag--info">
                                    ID <?= e((string) $event['id']) ?>
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
