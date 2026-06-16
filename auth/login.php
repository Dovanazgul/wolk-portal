<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

guest_only();

$systemName = app_visible_name();

$pageTitle = 'Portal del empleado | ' . $systemName;
$pageDescription = 'Acceso interno al Portal del empleado de Wolk IT.';

$error = '';

if (is_post()) {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Ingresa tu correo y contraseña.';
    } else {
        $statement = db()->prepare("
            SELECT
                id,
                full_name,
                email,
                password_hash,
                status
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $statement->execute([
            ':email' => $email,
        ]);

        $user = $statement->fetch();

        if (!$user || $user['status'] !== 'activo' || empty($user['password_hash'])) {
            auth_log_event('login_failed', null, $email);
            $error = 'El usuario no existe o no tiene acceso activo.';
        } elseif (!password_verify($password, (string) $user['password_hash'])) {
            auth_log_event('login_failed', (int) $user['id'], (string) $user['email']);
            $error = 'Correo o contraseña incorrectos.';
        } else {
            $updateLogin = db()->prepare("
                UPDATE users
                SET last_login_at = NOW()
                WHERE id = :id
            ");

            $updateLogin->execute([
                ':id' => $user['id'],
            ]);

            login_user($user);
            auth_redirect_after_login();
        }
    }
}

require_once __DIR__ . '/../includes/head.php';

?>

<main class="auth-page auth-page--employee">
    <section class="auth-card auth-card--center">
        <div class="brand" style="margin-bottom:22px;">
            <img
                class="brand__img"
                src="<?= e(asset_url('img/wolk_it_services_logo.jpeg')) ?>"
                alt="WOLK-IT">

            <span class="brand__text">
                <strong class="brand__name">WOLK-IT</strong>
                <span class="brand__sub">Portal del empleado</span>
                <span class="brand__tag">Acceso interno</span>
            </span>
        </div>

        <h1>Iniciar sesión</h1>

        <p>
            Ingresa con tu cuenta autorizada para acceder al Portal del empleado.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert--danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input
                    class="form-control"
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="correo@wolk-it.com"
                    required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input
                    class="form-control"
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Ingresa tu contraseña"
                    required>
            </div>

            <button class="btn btn--primary" type="submit" style="width:100%;">
                Entrar al Portal del empleado
            </button>
        </form>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('auth/forgot-password.php')) ?>">
                Olvidé mi contraseña
            </a>
        </div>
    </section>
</main>

<?php

require_once __DIR__ . '/../includes/auth-footer.php';
