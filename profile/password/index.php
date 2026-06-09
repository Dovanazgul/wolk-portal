<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

require_auth();

$currentUser = auth_user();

if (!$currentUser) {
    redirect('auth/login.php');
}

$systemName = app_visible_name();

$pageTitle = 'Cambiar contraseña | ' . $systemName;
$pageDescription = 'Actualización de contraseña de acceso al Portal interno.';

$error = '';

if (is_post()) {
    verify_csrf();

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Completa todos los campos.';
    } elseif (strlen($newPassword) < 10) {
        $error = 'La nueva contraseña debe tener al menos 10 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $error = 'La nueva contraseña debe incluir al menos una letra mayúscula.';
    } elseif (!preg_match('/[a-z]/', $newPassword)) {
        $error = 'La nueva contraseña debe incluir al menos una letra minúscula.';
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $error = 'La nueva contraseña debe incluir al menos un número.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        $error = 'La nueva contraseña debe incluir al menos un carácter especial.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'La confirmación no coincide con la nueva contraseña.';
    } elseif ($currentPassword === $newPassword) {
        $error = 'La nueva contraseña debe ser diferente a la actual.';
    } else {
        $statement = db()->prepare("
            SELECT
                id,
                email,
                password_hash
            FROM users
            WHERE id = :id
            AND status = 'activo'
            LIMIT 1
        ");

        $statement->execute([
            ':id' => $currentUser['id'],
        ]);

        $user = $statement->fetch();

        if (!$user || !password_verify($currentPassword, (string) $user['password_hash'])) {
            $error = 'La contraseña actual no es correcta.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $updateStatement = db()->prepare("
                UPDATE users
                SET
                    password_hash = :password_hash,
                    must_change_password = 0,
                    password_changed_at = NOW()
                WHERE id = :id
            ");

            $updateStatement->execute([
                ':password_hash' => $newHash,
                ':id' => $currentUser['id'],
            ]);

            auth_log_event(
                'password_changed',
                (int) $currentUser['id'],
                (string) $currentUser['email']
            );

            $updatedUser = auth_user();

            if (auth_profile_needs_confirmation($updatedUser)) {
                redirect('profile/confirm');
            }

            redirect('profile');
        }
    }
}

require_once __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<section class="intro-block">
    <div class="eyebrow">Seguridad de la cuenta</div>

    <h1>Cambiar contraseña</h1>

    <p>
        Actualiza tu contraseña de acceso al Portal interno. Si es tu primer ingreso, este paso es obligatorio.
    </p>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Acceso seguro</div>
            <h2>Actualización de contraseña</h2>
            <p>
                Usa una contraseña segura y diferente a la contraseña temporal.
            </p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert--danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="current_password">Contraseña actual</label>
            <input
                class="form-control"
                type="password"
                id="current_password"
                name="current_password"
                placeholder="Ingresa tu contraseña actual"
                required>
        </div>

        <div class="form-group">
            <label for="new_password">Nueva contraseña</label>
            <input
                class="form-control"
                type="password"
                id="new_password"
                name="new_password"
                placeholder="Mínimo 10 caracteres"
                required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmar nueva contraseña</label>
            <input
                class="form-control"
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Repite la nueva contraseña"
                required>
        </div>

        <div class="newsletter-meta">
            <span class="tag tag--info">Mínimo 10 caracteres</span>
            <span class="tag tag--info">Mayúscula y minúscula</span>
            <span class="tag tag--info">Número</span>
            <span class="tag tag--info">Carácter especial</span>
        </div>

        <div class="hero-actions">
            <button class="btn btn--primary" type="submit">
                Guardar contraseña
            </button>

            <a class="btn btn--ghost" href="<?= e(base_url('profile')) ?>">
                Volver a mi perfil
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('auth/logout.php')) ?>">
                Cerrar sesión
            </a>
        </div>
    </form>
</section>

<?php

require_once __DIR__ . '/../../includes/footer.php';
