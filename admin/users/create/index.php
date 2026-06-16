<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth.php';

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

$pageTitle = 'Crear usuario | ' . $systemName;
$pageDescription = 'Alta de usuarios internos del Portal interno.';

$error = '';
$success = '';
$temporaryPassword = '';

$areas = [];
$departments = [];
$roles = [];
$allowedRoleIds = [];

if ($canManageUsers) {
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
}

if ($canManageUsers && is_post()) {
    verify_csrf();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $positionName = trim((string) ($_POST['position_name'] ?? ''));
    $areaId = (int) ($_POST['area_id'] ?? 0);
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'activo');
    $selectedRoles = $_POST['roles'] ?? [];

    if (!is_array($selectedRoles)) {
        $selectedRoles = [];
    }

    $selectedRoles = array_values(array_unique(array_map('intval', $selectedRoles)));
    $invalidRoles = array_diff($selectedRoles, $allowedRoleIds);

    if ($fullName === '' || $email === '' || $positionName === '') {
        $error = 'Completa nombre, correo y puesto.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo válido.';
    } elseif ($areaId <= 0) {
        $error = 'Selecciona un área.';
    } elseif ($departmentId <= 0) {
        $error = 'Selecciona un departamento.';
    } elseif (!in_array($status, ['activo', 'inactivo'], true)) {
        $error = 'Selecciona un estado válido.';
    } elseif (!$selectedRoles) {
        $error = 'Selecciona al menos un rol.';
    } elseif ($invalidRoles) {
        $error = 'Seleccionaste un rol que no está permitido para tu nivel de acceso.';
    } else {
        $existsStatement = db()->prepare("
            SELECT id
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $existsStatement->execute([
            ':email' => $email,
        ]);

        if ($existsStatement->fetchColumn()) {
            $error = 'Ya existe un usuario registrado con ese correo.';
        } else {
            try {
                db()->beginTransaction();

                $temporaryPassword = 'Portal-' . bin2hex(random_bytes(4)) . '*A1';
                $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

                $insertUser = db()->prepare("
                    INSERT INTO users (
                        area_id,
                        primary_department_id,
                        full_name,
                        email,
                        position_name,
                        password_hash,
                        must_change_password,
                        status
                    ) VALUES (
                        :area_id,
                        :department_id,
                        :full_name,
                        :email,
                        :position_name,
                        :password_hash,
                        1,
                        :status
                    )
                ");

                $insertUser->execute([
                    ':area_id' => $areaId,
                    ':department_id' => $departmentId,
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':position_name' => $positionName,
                    ':password_hash' => $passwordHash,
                    ':status' => $status,
                ]);

                $newUserId = (int) db()->lastInsertId();

                $assignDepartment = db()->prepare("
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

                $assignDepartment->execute([
                    ':user_id' => $newUserId,
                    ':department_id' => $departmentId,
                ]);

                $assignRole = db()->prepare("
                    INSERT INTO user_roles (
                        user_id,
                        role_id
                    ) VALUES (
                        :user_id,
                        :role_id
                    )
                ");

                foreach ($selectedRoles as $roleId) {
                    $assignRole->execute([
                        ':user_id' => $newUserId,
                        ':role_id' => $roleId,
                    ]);
                }

                db()->commit();

                $success = 'Usuario creado correctamente. Copia la contraseña temporal antes de salir de esta pantalla.';
                $_POST = [];
            } catch (Throwable $exception) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }

                $temporaryPassword = '';
                $error = 'No se pudo crear el usuario.';
            }
        }
    }
}

require_once __DIR__ . '/../../../includes/head.php';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';

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
<?php else: ?>
    <section class="intro-block">
        <div class="eyebrow">Administración de usuarios</div>

        <h1>Crear usuario</h1>

        <p>
            Registra una nueva cuenta interna con área, departamento y roles asignados.
        </p>

        <div class="hero-actions">
            <a class="btn btn--ghost" href="<?= e(base_url('admin/users')) ?>">
                Volver a usuarios
            </a>
        </div>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Alta de cuenta</div>
                <h2>Datos del usuario</h2>
                <p>
                    La cuenta se creará con cambio de contraseña obligatorio en el primer inicio de sesión.
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

        <?php if ($temporaryPassword !== ''): ?>
            <div class="alert" style="background:#fff3cd;color:#664d03;border:1px solid #ffecb5;">
                <strong>Contraseña temporal:</strong>
                <?= e($temporaryPassword) ?>
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
                    value="<?= e($_POST['full_name'] ?? '') ?>"
                    placeholder="Nombre completo del colaborador"
                    required>
            </div>

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
                <label for="position_name">Puesto</label>
                <input
                    class="form-control"
                    type="text"
                    id="position_name"
                    name="position_name"
                    value="<?= e($_POST['position_name'] ?? '') ?>"
                    placeholder="Puesto del colaborador"
                    required>
            </div>

            <div class="form-group">
                <label for="area_id">Área</label>
                <select class="form-control" id="area_id" name="area_id" required>
                    <option value="">Selecciona un área</option>

                    <?php foreach ($areas as $area): ?>
                        <option
                            value="<?= e((string) $area['id']) ?>"
                            <?= (int) ($_POST['area_id'] ?? 0) === (int) $area['id'] ? 'selected' : '' ?>>
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
                        <option
                            value="<?= e((string) $department['id']) ?>"
                            <?= (int) ($_POST['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>>
                            <?= e((string) $department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Estado</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="activo" <?= ($_POST['status'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>
                        Activo
                    </option>

                    <option value="inactivo" <?= ($_POST['status'] ?? '') === 'inactivo' ? 'selected' : '' ?>>
                        Inactivo
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Roles</label>

                <div class="newsletter-meta">
                    <?php foreach ($roles as $role): ?>
                        <?php $checked = in_array((int) $role['id'], array_map('intval', $_POST['roles'] ?? []), true); ?>

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

            <div class="hero-actions">
                <button class="btn btn--primary" type="submit">
                    Crear usuario
                </button>

                <a class="btn btn--ghost" href="<?= e(base_url('admin/users')) ?>">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../../../includes/footer.php';
