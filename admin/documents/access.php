<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/document_access_admin.php';

require_login();
require_password_updated();
require_profile_confirmed();
document_access_admin_require_manage();

$systemName = app_visible_name();
$pageTitle = 'Ajustar acceso documental | ' . $systemName;
$pageDescription = 'Configuración de clasificación, alcance y acceso manual para documentos internos.';

$documentId = (int) ($_GET['document_id'] ?? $_GET['id'] ?? 0);
$success = '';
$error = '';

if ($documentId <= 0) {
    http_response_code(400);
    exit('Documento no válido.');
}

function document_access_page_columns(string $tableName): array
{
    $statement = db()->query("DESCRIBE {$tableName}");
    $columns = [];

    foreach ($statement->fetchAll() as $column) {
        $columns[] = (string) $column['Field'];
    }

    return $columns;
}

function document_access_page_title_column(array $columns): ?string
{
    $possibleColumns = [
        'title',
        'name',
        'document_name',
        'file_name',
        'original_name',
        'filename',
    ];

    foreach ($possibleColumns as $column) {
        if (in_array($column, $columns, true)) {
            return $column;
        }
    }

    return null;
}

function document_access_page_document(int $documentId): ?array
{
    $statement = db()->prepare("
        SELECT
            d.*,
            dc.name AS classification_name,
            dc.code AS classification_code
        FROM documents d
        LEFT JOIN document_classifications dc
            ON dc.id = d.classification_id
        WHERE d.id = :id
        LIMIT 1
    ");

    $statement->execute([
        ':id' => $documentId,
    ]);

    $document = $statement->fetch();

    return $document ?: null;
}

function document_access_page_selected_values(array $rules, string $accessType, string $field): array
{
    $values = [];

    foreach ($rules as $rule) {
        if (($rule['access_type'] ?? '') === $accessType) {
            $values[] = (string) ($rule[$field] ?? '');
        }
    }

    return array_values(array_filter($values, static fn($value) => $value !== ''));
}

$document = document_access_page_document($documentId);

if (!$document) {
    http_response_code(404);
    exit('Documento no encontrado.');
}

$classifications = document_access_admin_classifications();
$areas = document_access_admin_areas();
$departments = document_access_admin_departments();
$users = document_access_admin_users();
$roles = document_access_admin_roles();

if (is_post()) {
    verify_csrf();

    $classificationId = (int) ($_POST['classification_id'] ?? 0);
    $ownerAreaId = (int) ($_POST['owner_area_id'] ?? 0);
    $ownerDepartmentId = (int) ($_POST['owner_department_id'] ?? 0);
    $manualAccessEnabled = isset($_POST['manual_access_enabled']);

    $allowedUserIds = $_POST['allowed_users'] ?? [];
    $deniedUserIds = $_POST['denied_users'] ?? [];
    $allowedRoles = $_POST['allowed_roles'] ?? [];
    $deniedRoles = $_POST['denied_roles'] ?? [];

    if ($classificationId <= 0) {
        $error = 'Selecciona una clasificación válida.';
    } else {
        document_access_admin_save_document_scope(
            $documentId,
            $classificationId,
            $ownerAreaId > 0 ? $ownerAreaId : null,
            $ownerDepartmentId > 0 ? $ownerDepartmentId : null,
            $manualAccessEnabled
        );

        if ($manualAccessEnabled) {
            document_access_admin_save_manual_access(
                $documentId,
                is_array($allowedUserIds) ? $allowedUserIds : [],
                is_array($deniedUserIds) ? $deniedUserIds : [],
                is_array($allowedRoles) ? $allowedRoles : [],
                is_array($deniedRoles) ? $deniedRoles : []
            );
        } else {
            document_access_admin_clear_manual_rules($documentId);
        }

        $success = 'Acceso documental actualizado correctamente.';
        $document = document_access_page_document($documentId);
    }
}

$roleRules = document_access_admin_get_role_rules($documentId);
$userRules = document_access_admin_get_user_rules($documentId);

$allowedRoles = document_access_page_selected_values($roleRules, 'allow', 'role_name');
$deniedRoles = document_access_page_selected_values($roleRules, 'deny', 'role_name');
$allowedUsers = document_access_page_selected_values($userRules, 'allow', 'user_id');
$deniedUsers = document_access_page_selected_values($userRules, 'deny', 'user_id');

$documentColumns = document_access_page_columns('documents');
$titleColumn = document_access_page_title_column($documentColumns);
$documentTitle = $titleColumn ? (string) ($document[$titleColumn] ?? 'Documento interno') : 'Documento interno';

require_once dirname(__DIR__, 2) . '/includes/head.php';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';

?>

<section class="intro-block">
    <div class="eyebrow">Control documental</div>

    <h1>Ajustar acceso</h1>

    <p>
        Define la clasificación del documento y, cuando sea necesario, agrega excepciones manuales para usuarios o perfiles específicos.
    </p>

    <div class="hero-actions">
        <a class="btn btn--ghost" href="<?= e(base_url('documentos')) ?>">
            Volver a documentos
        </a>
    </div>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Documento</div>
            <h2><?= e($documentTitle) ?></h2>
            <p>
                La configuración automática usa área, departamento y clasificación. El ajuste manual solo debe activarse cuando el documento requiera excepciones.
            </p>
        </div>

        <span class="pill">
            <?= e((string) ($document['classification_name'] ?? 'Sin clasificación')) ?>
        </span>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert--success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert--danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <?= csrf_field() ?>

        <div class="top-grid">
            <article class="service-board">
                <div class="service-board__head">
                    <h3 class="service-board__title">Clasificación y alcance</h3>
                    <span class="pill">Base automática</span>
                </div>

                <div class="form-group">
                    <label for="classification_id">Clasificación</label>
                    <select class="form-control" id="classification_id" name="classification_id" required>
                        <option value="">Selecciona una clasificación</option>

                        <?php foreach ($classifications as $classification): ?>
                            <option
                                value="<?= e((string) $classification['id']) ?>"
                                <?= (int) ($document['classification_id'] ?? 0) === (int) $classification['id'] ? 'selected' : '' ?>>
                                <?= e((string) $classification['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="owner_area_id">Área propietaria</label>
                    <select class="form-control" id="owner_area_id" name="owner_area_id">
                        <option value="">Sin área específica</option>

                        <?php foreach ($areas as $area): ?>
                            <option
                                value="<?= e((string) $area['id']) ?>"
                                <?= (int) ($document['owner_area_id'] ?? 0) === (int) $area['id'] ? 'selected' : '' ?>>
                                <?= e((string) $area['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="owner_department_id">Departamento propietario</label>
                    <select class="form-control" id="owner_department_id" name="owner_department_id">
                        <option value="">Sin departamento específico</option>

                        <?php foreach ($departments as $department): ?>
                            <option
                                value="<?= e((string) $department['id']) ?>"
                                <?= (int) ($document['owner_department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>>
                                <?= e((string) $department['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="check-row">
                    <input
                        type="checkbox"
                        name="manual_access_enabled"
                        value="1"
                        <?= (int) ($document['manual_access_enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
                    <span>Activar ajuste manual para este documento</span>
                </label>
            </article>

            <article class="service-board">
                <div class="service-board__head">
                    <h3 class="service-board__title">Regla sugerida</h3>
                    <span class="pill">Referencia</span>
                </div>

                <div class="service-list">
                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Público interno / Interno</strong>
                            <span>Visible para usuarios activos del sistema.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Departamental / Confidencial</strong>
                            <span>Visible por coincidencia de área o departamento.</span>
                        </span>
                    </div>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Restringido / Crítico</strong>
                            <span>Requiere ajuste manual o acceso superior.</span>
                        </span>
                    </div>
                </div>
            </article>
        </div>

        <section class="card section">
            <div class="section-head">
                <div>
                    <div class="eyebrow">Ajuste manual</div>
                    <h2>Excepciones del documento</h2>
                    <p>
                        Usa esta parte solo cuando el documento no deba seguir únicamente la regla automática por área o departamento.
                    </p>
                </div>

                <span class="pill">Opcional</span>
            </div>

            <div class="top-grid">
                <article class="service-board">
                    <div class="service-board__head">
                        <h3 class="service-board__title">Permitir perfiles</h3>
                        <span class="pill">Roles</span>
                    </div>

                    <div class="form-group">
                        <label for="allowed_roles">Roles permitidos</label>
                        <select class="form-control" id="allowed_roles" name="allowed_roles[]" multiple size="7">
                            <?php foreach ($roles as $role): ?>
                                <option
                                    value="<?= e($role) ?>"
                                    <?= in_array($role, $allowedRoles, true) ? 'selected' : '' ?>>
                                    <?= e($role) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="denied_roles">Roles bloqueados</label>
                        <select class="form-control" id="denied_roles" name="denied_roles[]" multiple size="7">
                            <?php foreach ($roles as $role): ?>
                                <option
                                    value="<?= e($role) ?>"
                                    <?= in_array($role, $deniedRoles, true) ? 'selected' : '' ?>>
                                    <?= e($role) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </article>

                <article class="service-board">
                    <div class="service-board__head">
                        <h3 class="service-board__title">Permitir usuarios</h3>
                        <span class="pill">Personas</span>
                    </div>

                    <div class="form-group">
                        <label for="allowed_users">Usuarios permitidos</label>
                        <select class="form-control" id="allowed_users" name="allowed_users[]" multiple size="7">
                            <?php foreach ($users as $user): ?>
                                <option
                                    value="<?= e((string) $user['id']) ?>"
                                    <?= in_array((string) $user['id'], $allowedUsers, true) ? 'selected' : '' ?>>
                                    <?= e((string) $user['full_name']) ?> — <?= e((string) $user['email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="denied_users">Usuarios bloqueados</label>
                        <select class="form-control" id="denied_users" name="denied_users[]" multiple size="7">
                            <?php foreach ($users as $user): ?>
                                <option
                                    value="<?= e((string) $user['id']) ?>"
                                    <?= in_array((string) $user['id'], $deniedUsers, true) ? 'selected' : '' ?>>
                                    <?= e((string) $user['full_name']) ?> — <?= e((string) $user['email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </article>
            </div>
        </section>

        <div class="hero-actions">
            <button class="btn btn--primary" type="submit">
                Guardar acceso
            </button>

            <a class="btn btn--ghost" href="<?= e(base_url('documentos')) ?>">
                Cancelar
            </a>
        </div>
    </form>
</section>

<?php

require_once dirname(__DIR__, 2) . '/includes/footer.php';
