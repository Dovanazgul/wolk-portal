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
$canManageUsers = auth_can_manage_users();
$isSuperadmin = auth_is_superadmin();
$userId = (int) ($_GET['id'] ?? 0);

$pageTitle = 'Editar usuario | ' . $systemName;
$pageDescription = 'Edición de usuarios internos del Portal interno.';

$error = '';
$success = '';

$user = null;
$areas = [];
$departments = [];
$roles = [];
$selectedRoles = [];
$allowedRoleIds = [];
$targetRoleSlugs = [];
$targetIsProtected = false;

if ($canManageUsers && $userId > 0) {
    $areas = db()->query("
        SELECT id, name, slug
        FROM areas
        ORDER BY name ASC
    ")->fetchAll();

    $departments = db()->query("
        SELECT id, name, slug
        FROM departments
        ORDER BY name ASC
    ")->fetchAll();

    if ($isSuperadmin) {
        $roles = db()->query("
            SELECT id, name, slug
            FROM roles
            ORDER BY name ASC
        ")->fetchAll();
    } else {
        $roles = db()->query("
            SELECT id, name, slug
            FROM roles
            WHERE slug NOT IN ('superadmin', 'ciso', 'cto')
            ORDER BY name ASC
        ")->fetchAll();
    }

    $allowedRoleIds = array_map('intval', array_column($roles, 'id'));

    $statement = db()->prepare("
        SELECT
            id,
            area_id,
            primary_department_id,
            full_name,
            email,
            position_name,
            status,
            must_change_password,
            profile_confirmed_at,
            email_verified_at
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $statement->execute([
        ':id' => $userId,
    ]);

    $user = $statement->fetch() ?: null;

    if ($user) {
        $rolesStatement = db()->prepare("
            SELECT
                r.id,
                r.slug
            FROM user_roles ur
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
        ");

        $rolesStatement->execute([
            ':user_id' => $userId,
        ]);

        $targetRoles = $rolesStatement->fetchAll();
        $selectedRoles = array_map('intval', array_column($targetRoles, 'id'));
        $targetRoleSlugs = array_map('strtolower', array_column($targetRoles, 'slug'));
        $targetIsProtected = count(array_intersect($targetRoleSlugs, ['superadmin', 'ciso', 'cto'])) > 0;
    }
}

if ($canManageUsers && $user && is_post()) {
    verify_csrf();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $positionName = trim((string) ($_POST['position_name'] ?? ''));
    $areaId = (int) ($_POST['area_id'] ?? 0);
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'activo');
    $mustChangePassword = isset($_POST['must_change_password']) ? 1 : 0;
    $clearProfileConfirmation = isset($_POST['clear_profile_confirmation']);
    $clearEmailVerification = isset($_POST['clear_email_verification']);
    $postedRoles = $_POST['roles'] ?? [];

    if (!is_array($postedRoles)) {
        $postedRoles = [];
    }

    $postedRoles = array_values(array_unique(array_map('intval', $postedRoles)));
    $invalidRoles = array_diff($postedRoles, $allowedRoleIds);

    if (!$isSuperadmin && $targetIsProtected) {
        $error = 'No tienes permiso para editar una cuenta crítica.';
    } elseif ($fullName === '' || $email === '' || $positionName === '') {
        $error = 'Completa nombre, correo y puesto.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo válido.';
    } elseif ($areaId <= 0) {
        $error = 'Selecciona un área.';
    } elseif ($departmentId <= 0) {
        $error = 'Selecciona un departamento.';
    } elseif (!in_array($status, ['activo', 'inactivo'], true)) {
        $error = 'Selecciona un estado válido.';
    } elseif (!$postedRoles) {
        $error = 'Selecciona al menos un rol.';
    } elseif ($invalidRoles) {
        $error = 'Seleccionaste un rol que no está permitido para tu nivel de acceso.';
    } elseif ((int) $user['id'] === (int) $currentUser['id'] && $status !== 'activo') {
        $error = 'No puedes desactivar tu propia cuenta desde edición.';
    } else {
        $existsStatement = db()->prepare("
            SELECT id
            FROM users
            WHERE email = :email
            AND id <> :id
            LIMIT 1
        ");

        $existsStatement->execute([
            ':email' => $email,
            ':id' => $userId,
        ]);

        if ($existsStatement->fetchColumn()) {
            $error = 'Ya existe otro usuario registrado con ese correo.';
        } else {
            try {
                db()->beginTransaction();

                $updateUser = db()->prepare("
                    UPDATE users
                    SET
                        area_id = :area_id,
                        primary_department_id = :department_id,
                        full_name = :full_name,
                        email = :email,
                        position_name = :position_name,
                        status = :status,
                        must_change_password = :must_change_password,
                        profile_confirmed_at = CASE
                            WHEN :clear_profile_confirmation = 1 THEN NULL
                            ELSE profile_confirmed_at
                        END,
                        email_verified_at = CASE
                            WHEN :clear_email_verification = 1 THEN NULL
                            ELSE email_verified_at
                        END,
                        updated_at = NOW()
                    WHERE id = :id
                ");

                $updateUser->execute([
                    ':area_id' => $areaId,
                    ':department_id' => $departmentId,
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':position_name' => $positionName,
                    ':status' => $status,
                    ':must_change_password' => $mustChangePassword,
                    ':clear_profile_confirmation' => $clearProfileConfirmation ? 1 : 0,
                    ':clear_email_verification' => $clearEmailVerification ? 1 : 0,
                    ':id' => $userId,
                ]);

                $deleteDepartments = db()->prepare("
                    DELETE FROM user_departments
                    WHERE user_id = :user_id
                ");

                $deleteDepartments->execute([
                    ':user_id' => $userId,
                ]);

                $insertDepartment = db()->prepare("
                    INSERT INTO user_departments (
                        user_id,
                        department_id,
                        is_primary
                    ) VALUES (
                        :user_id,
                        :department_id,
                        1
                    )
                ");

                $insertDepartment->execute([
                    ':user_id' => $userId,
                    ':department_id' => $departmentId,
                ]);

                $deleteRoles = db()->prepare("
                    DELETE FROM user_roles
                    WHERE user_id = :user_id
                ");

                $deleteRoles->execute([
                    ':user_id' => $userId,
                ]);

                $insertRole = db()->prepare("
                    INSERT INTO user_roles (
                        user_id,
                        role_id
                    ) VALUES (
                        :user_id,
                        :role_id
                    )
                ");

                foreach ($postedRoles as $roleId) {
                    $insertRole->execute([
                        ':user_id' => $userId,
                        ':role_id' => $roleId,
                    ]);
                }

                db()->commit();

                $success = 'Usuario actualizado correctamente.';

                $statement->execute([
                    ':id' => $userId,
                ]);

                $user = $statement->fetch() ?: null;
                $selectedRoles = $postedRoles;
            } catch (Throwable $exception) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }

                $error = 'No se pudo actualizar el usuario.';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<?php if (!$canManageUsers): ?>
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
<?php elseif (!$user): ?>
    <section class="intro-block">
        <div class="eyebrow">Usuario no encontrado</div>

        <h1>No se encontró la cuenta</h1>

        <p>
            El usuario solicitado no existe o no está disponible.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('admin/users')) ?>">
                Volver a usuarios
            </a>
        </div>
    </section>
<?php elseif (!$isSuperadmin && $targetIsProtected): ?>
    <section class="intro-block">
        <div class="eyebrow">Acceso restringido</div>

        <h1>No puedes editar esta cuenta</h1>

        <p>
            Esta cuenta tiene permisos críticos y solo puede ser modificada por SUPERADMIN, CISO o CTO.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('admin/users/show.php?id=' . (int) $user['id'])) ?>">
                Volver al detalle
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('admin/users')) ?>">
                Volver a usuarios
            </a>
        </div>
    </section>
<?php else: ?>
    <section class="intro-block">
        <div class="eyebrow">Administración de usuarios</div>

        <h1>Editar usuario</h1>

        <p>
            Actualiza la información, roles, área y departamento principal de la cuenta.
        </p>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('admin/users/show.php?id=' . (int) $user['id'])) ?>">
                Ver detalle
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('admin/users')) ?>">
                Volver a usuarios
            </a>
        </div>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Edición de cuenta</div>
                <h2>Datos del usuario</h2>
                <p>
                    Los cambios aplican de inmediato en los accesos del Portal interno.
                </p>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert--danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert" style="background:#d1e7dd;color:#0f5132;border:1px solid #badbcc;">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="full_name">Nombre completo</label>
                <input
                    class="form-control"
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input
                    class="form-control"
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? $user['email']) ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="position_name">Puesto</label>
                <input
                    class="form-control"
                    type="text"
                    id="position_name"
                    name="position_name"
                    value="<?= e($_POST['position_name'] ?? $user['position_name']) ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="area_id">Área</label>
                <select class="form-control" id="area_id" name="area_id" required>
                    <option value="">Selecciona un área</option>

                    <?php foreach ($areas as $area): ?>
                        <?php $currentAreaId = (int) ($_POST['area_id'] ?? $user['area_id']); ?>

                        <option
                            value="<?= e((string) $area['id']) ?>"
                            <?= $currentAreaId === (int) $area['id'] ? 'selected' : '' ?>>
                            <?= e((string) $area['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="department_id">Departamento principal</label>
                <select class="form-control" id="department_id" name="department_id" required>
                    <option value="">Selecciona un departamento</option>

                    <?php foreach ($departments as $department): ?>
                        <?php $currentDepartmentId = (int) ($_POST['department_id'] ?? $user['primary_department_id']); ?>

                        <option
                            value="<?= e((string) $department['id']) ?>"
                            <?= $currentDepartmentId === (int) $department['id'] ? 'selected' : '' ?>>
                            <?= e((string) $department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Estado</label>
                <select class="form-control" id="status" name="status" required>
                    <?php $currentStatus = (string) ($_POST['status'] ?? $user['status']); ?>

                    <option value="activo" <?= $currentStatus === 'activo' ? 'selected' : '' ?>>
                        Activo
                    </option>

                    <option value="inactivo" <?= $currentStatus === 'inactivo' ? 'selected' : '' ?>>
                        Inactivo
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Roles</label>

                <div class="newsletter-meta">
                    <?php foreach ($roles as $role): ?>
                        <?php
                        $postedRoleValues = $_POST['roles'] ?? $selectedRoles;

                        if (!is_array($postedRoleValues)) {
                            $postedRoleValues = [];
                        }

                        $postedRoleValues = array_map('intval', $postedRoleValues);
                        $checked = in_array((int) $role['id'], $postedRoleValues, true);
                        ?>

                        <label class="tag tag--info" style="cursor:pointer;">
                            <input
                                type="checkbox"
                                name="roles[]"
                                value="<?= e((string) $role['id']) ?>"
                                <?= $checked ? 'checked' : '' ?>
                                style="margin-right:6px;">
                            <?= e(strtoupper((string) $role['slug'])) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Control de acceso</label>

                <div class="newsletter-meta">
                    <label class="tag tag--warning" style="cursor:pointer;">
                        <input
                            type="checkbox"
                            name="must_change_password"
                            value="1"
                            <?= (int) ($_POST['must_change_password'] ?? $user['must_change_password']) === 1 ? 'checked' : '' ?>
                            style="margin-right:6px;">
                        Solicitar cambio de contraseña
                    </label>

                    <label class="tag tag--warning" style="cursor:pointer;">
                        <input
                            type="checkbox"
                            name="clear_profile_confirmation"
                            value="1"
                            style="margin-right:6px;">
                        Solicitar confirmación de perfil
                    </label>

                    <label class="tag tag--warning" style="cursor:pointer;">
                        <input
                            type="checkbox"
                            name="clear_email_verification"
                            value="1"
                            style="margin-right:6px;">
                        Solicitar verificación de correo
                    </label>
                </div>
            </div>

            <div class="hero-actions">
                <button class="btn btn--primary" type="submit">
                    Guardar cambios
                </button>

                <a class="btn btn--ghost" href="<?= e(base_url('admin/users/show.php?id=' . (int) $user['id'])) ?>">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../includes/footer.php';
