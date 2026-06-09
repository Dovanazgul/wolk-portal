<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

require_auth();

$currentUser = auth_user();

if (!$currentUser) {
    redirect('auth/login.php');
}

if (auth_must_change_password($currentUser)) {
    redirect('profile/password');
}

if (!auth_profile_needs_confirmation($currentUser)) {
    redirect('/');
}

$systemName = app_visible_name();
$roles = auth_roles((int) $currentUser['id']);

$pageTitle = 'Confirmar perfil | ' . $systemName;
$pageDescription = 'Confirmación de datos de usuario dentro del Portal interno.';

$error = '';

if (is_post()) {
    verify_csrf();

    $confirmProfile = (string) ($_POST['confirm_profile'] ?? '');

    if ($confirmProfile !== '1') {
        $error = 'Debes confirmar que tus datos son correctos para continuar.';
    } else {
        $statement = db()->prepare("
            UPDATE users
            SET profile_confirmed_at = NOW()
            WHERE id = :id
        ");

        $statement->execute([
            ':id' => $currentUser['id'],
        ]);

        auth_log_event(
            'profile_confirmed',
            (int) $currentUser['id'],
            (string) $currentUser['email']
        );

        redirect('/');
    }
}

require_once __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<section class="intro-block">
    <div class="eyebrow">Confirmación de cuenta</div>

    <h1>Confirma tu perfil</h1>

    <p>
        Antes de continuar, revisa que tu información de acceso sea correcta. El Portal interno usa estos datos
        para mostrar documentos, solicitudes y módulos según tu rol, área y departamento.
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
                <span class="brand__tag">Pendiente de confirmación</span>
            </span>
        </div>

        <p>
            Esta validación ayuda a evitar accesos incorrectos y mantiene la información interna mejor controlada.
        </p>
    </article>

    <article class="card welcome-card">
        <h1>Roles asignados</h1>

        <p>
            Estos roles definen el nivel de acceso que tendrás dentro del Portal interno.
        </p>

        <div class="newsletter-meta">
            <?php foreach ($roles as $role): ?>
                <span class="tag tag--info">
                    <?= e(strtoupper((string) $role)) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Datos registrados</div>
            <h2>Revisión de información</h2>
            <p>
                Verifica cuidadosamente tu correo, puesto, área y departamento.
            </p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert--danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

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
            </div>
        </div>
    </div>

    <form method="POST" style="margin-top:20px;">
        <?= csrf_field() ?>

        <label class="profile-confirm-check">
            <input type="checkbox" name="confirm_profile" value="1">
            <span>
                Confirmo que mi nombre, correo, puesto, área y departamento son correctos.
            </span>
        </label>

        <div class="hero-actions">
            <button class="btn btn--primary" type="submit">
                Confirmar y continuar
            </button>

            <a
                class="btn btn--ghost"
                href="mailto:support-portal@wolk-it.com?subject=Corrección%20de%20datos%20en%20Wolk-It%20Portal%20interno">
                Reportar datos incorrectos
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('auth/logout.php')) ?>">
                Cerrar sesión
            </a>
        </div>
    </form>
</section>

<?php

require_once __DIR__ . '/../../includes/footer.php';
