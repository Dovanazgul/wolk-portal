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
$canViewSettings = auth_can_view_settings();

$pageTitle = 'Configuración | ' . $systemName;
$pageDescription = 'Configuración general del Portal interno.';

$environment = app_environment();
$publicUrl = app_url('/');
$baseUrl = base_url('/');
$mailHost = (string) mail_config('host');
$mailPort = (string) mail_config('port');
$mailEncryption = (string) mail_config('encryption');
$mailFrom = (string) mail_config('from_email');
$mailReplyTo = (string) mail_config('reply_to_email');

require_once __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<?php if (!$canViewSettings): ?>
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
        <div class="eyebrow">Configuración</div>

        <h1>Configuración del Portal interno</h1>

        <p>
            Revisa el entorno actual, URLs principales y configuración de correo del sistema.
        </p>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('admin')) ?>">
                Volver al panel
            </a>
        </div>
    </section>

    <section class="top-grid">
        <article class="card welcome-card">
            <h1>Entorno</h1>

            <p>
                Estado actual de ejecución del Portal interno.
            </p>

            <div class="newsletter-meta">
                <?php if ($environment === 'production'): ?>
                    <span class="tag tag--ok">Producción</span>
                <?php else: ?>
                    <span class="tag tag--warning">Desarrollo local</span>
                <?php endif; ?>
            </div>
        </article>

        <article class="card welcome-card">
            <h1>Correo</h1>

            <p>
                Configuración activa para recuperación, verificación y notificaciones.
            </p>

            <div class="newsletter-meta">
                <span class="tag tag--info"><?= e($mailHost) ?></span>
                <span class="tag tag--ok"><?= e($mailEncryption) ?></span>
            </div>
        </article>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Parámetros activos</div>
                <h2>Información general</h2>
                <p>
                    Estos datos ayudan a validar que el Portal interno esté apuntando al entorno correcto.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <div class="service-item">
                        <span class="service-ico service-ico--blue" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="20"></circle>
                                <path d="M22 32h20"></path>
                                <path d="M32 22v20"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Nombre visible</strong>
                            <span><?= e($systemName) ?></span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico service-ico--green" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M14 32h36"></path>
                                <path d="M38 20l12 12-12 12"></path>
                                <path d="M14 20v24"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Ruta local</strong>
                            <span><?= e($baseUrl) ?></span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-ico" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="20"></circle>
                                <path d="M12 32h40"></path>
                                <path d="M32 12c7 7 7 33 0 40"></path>
                                <path d="M32 12c-7 7-7 33 0 40"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>URL pública configurada</strong>
                            <span><?= e($publicUrl) ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Correo saliente</div>
                <h2>Configuración SMTP</h2>
                <p>
                    La contraseña no se muestra por seguridad.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Servidor SMTP</strong>
                            <span><?= e($mailHost) ?>:<?= e($mailPort) ?></span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Cifrado</strong>
                            <span><?= e(strtoupper($mailEncryption)) ?></span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Remitente</strong>
                            <span><?= e($mailFrom) ?></span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Responder a</strong>
                            <span><?= e($mailReplyTo) ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Producción</div>
                <h2>Cambio final recomendado</h2>
                <p>
                    Cuando el Portal interno se publique en servidor real, estos son los puntos a modificar.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-list">
                    <div class="service-item">
                        <span class="service-copy">
                            <strong>config/app.php</strong>
                            <span>Cambiar environment a production y confirmar la URL pública final.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>config/mail.php</strong>
                            <span>Usar la configuración productiva con correo del dominio wolk-it.com.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Google Workspace</strong>
                            <span>Autorizar el servidor real para envío corporativo mediante SMTP Relay.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Archivos de prueba</strong>
                            <span>Eliminar archivos temporales antes de publicar.</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
