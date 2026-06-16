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
$roles = auth_roles((int) $currentUser['id']);
$emailVerified = !empty($currentUser['email_verified_at']);
$profileConfirmedAt = !empty($currentUser['profile_confirmed_at'])
    ? date('d/m/Y H:i', strtotime((string) $currentUser['profile_confirmed_at']))
    : 'Pendiente';

$pageTitle = 'Mi perfil | ' . $systemName;
$pageDescription = 'Información del usuario dentro de ' . $systemName . '.';

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<section class="intro-block">
    <div class="eyebrow">Perfil de usuario</div>

    <h1>Mi perfil</h1>

    <p>
        Consulta la información asociada a tu cuenta dentro del Portal interno.
    </p>
</section>

<section class="top-grid">
    <article class="card welcome-card">
        <div class="brand" style="margin-bottom:18px;">
            <img
                class="brand__img"
                src="<?= e(auth_photo_url($currentUser)) ?>"
                alt="Perfil">

            <span class="brand__text">
                <strong class="brand__name"><?= e((string) $currentUser['full_name']) ?></strong>
                <span class="brand__sub"><?= e((string) $currentUser['position_name']) ?></span>
                <span class="brand__tag">Usuario activo</span>
            </span>
        </div>

        <p>
            Tu cuenta está vinculada a los permisos del Portal interno mediante rol, área y departamento.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('profile/password')) ?>">
                Cambiar contraseña
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('auth/logout.php')) ?>">
                Cerrar sesión
            </a>
        </div>
    </article>

    <article class="card welcome-card">
        <h1>Correo corporativo</h1>

        <p>
            La verificación de correo ayuda a proteger el acceso y confirmar que la cuenta pertenece al usuario correcto.
        </p>

        <div class="newsletter-meta">
            <?php if ($emailVerified): ?>
                <span class="tag tag--ok">
                    Correo verificado
                </span>
            <?php else: ?>
                <span class="tag tag--warning">
                    Verificación pendiente
                </span>
            <?php endif; ?>
        </div>

        <div class="hero-actions">
            <?php if (!$emailVerified): ?>
                <a class="btn btn--primary" href="<?= e(base_url('auth/send-verification.php')) ?>">
                    Enviar verificación
                </a>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Información general</div>
            <h2>Datos de la cuenta</h2>
            <p>
                Esta información se toma directamente de la base de datos del Portal interno.
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
                        <span><?= e((string) $currentUser['full_name']) ?></span>
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
                        <span><?= e((string) $currentUser['email']) ?></span>
                    </span>

                    <span class="service-right">
                        <?php if ($emailVerified): ?>
                            <span class="tag tag--ok">Verificado</span>
                        <?php else: ?>
                            <span class="tag tag--warning">Pendiente</span>
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
                        <span><?= e((string) $currentUser['position_name']) ?></span>
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
                        <span><?= e((string) $currentUser['area_name']) ?></span>
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
                        <strong>Departamento</strong>
                        <span><?= e((string) $currentUser['department_name']) ?></span>
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
                        <strong>Estado de perfil</strong>
                        <span>Confirmado el <?= e($profileConfirmedAt) ?></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Accesos asignados</div>
            <h2>Roles de usuario</h2>
            <p>
                Estos roles definen el nivel de acceso que tienes dentro del Portal interno.
            </p>
        </div>
    </div>

    <div class="newsletter-meta">
        <?php foreach ($roles as $role): ?>
            <span class="tag tag--info">
                <?= e(strtoupper((string) $role)) ?>
            </span>
        <?php endforeach; ?>
    </div>
</section>

<?php

require_once __DIR__ . '/../includes/footer.php';
