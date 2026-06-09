<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/document_access.php';
require_once dirname(__DIR__, 2) . '/includes/document_access_admin.php';
require_once dirname(__DIR__, 2) . '/includes/document_access_list.php';

require_auth();
require_password_updated();
require_profile_confirmed();
document_access_admin_require_manage();

$systemName = app_visible_name();
$pageTitle = 'Prueba de acceso documental | ' . $systemName;
$pageDescription = 'Prueba interna para validar clasificación, departamento, área y permisos manuales.';

$currentUser = auth_user();
$selectedUserId = (int) ($_GET['user_id'] ?? ($currentUser['id'] ?? 0));

$users = document_access_admin_users();

$documentsStatement = db()->query("
    SELECT
        d.*,
        dc.name AS classification_name,
        dc.code AS classification_code,
        a.name AS owner_area_name,
        dep.name AS owner_department_name
    FROM documents d
    LEFT JOIN document_classifications dc
        ON dc.id = d.classification_id
    LEFT JOIN areas a
        ON a.id = d.owner_area_id
    LEFT JOIN departments dep
        ON dep.id = d.owner_department_id
    ORDER BY d.id DESC
");

$documents = $documentsStatement->fetchAll();

$selectedUser = null;

foreach ($users as $user) {
    if ((int) $user['id'] === $selectedUserId) {
        $selectedUser = $user;
        break;
    }
}

require_once dirname(__DIR__, 2) . '/includes/head.php';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';

?>

<section class="intro-block">
    <div class="eyebrow">Prueba interna</div>

    <h1>Acceso documental</h1>

    <p>
        Valida qué documentos puede ver un usuario considerando clasificación, área, departamento y permisos manuales.
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
            <div class="eyebrow">Usuario de prueba</div>
            <h2>Seleccionar usuario</h2>
            <p>
                Cambia el usuario para revisar si el acceso se está aplicando correctamente.
            </p>
        </div>

        <span class="pill">
            <?= e((string) count($documents)) ?> documentos revisados
        </span>
    </div>

    <form method="GET" class="top-grid">
        <div class="form-group">
            <label for="user_id">Usuario</label>
            <select class="form-control" id="user_id" name="user_id">
                <?php foreach ($users as $user): ?>
                    <option
                        value="<?= e((string) $user['id']) ?>"
                        <?= (int) $user['id'] === $selectedUserId ? 'selected' : '' ?>>
                        <?= e((string) $user['full_name']) ?> — <?= e((string) $user['email']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>&nbsp;</label>
            <button class="btn btn--primary" type="submit">
                Probar acceso
            </button>
        </div>
    </form>

    <?php if ($selectedUser): ?>
        <div class="newsletter-meta">
            <span class="tag tag--info">
                Usuario: <?= e((string) $selectedUser['full_name']) ?>
            </span>

            <span class="tag tag--ok">
                ID: <?= e((string) $selectedUserId) ?>
            </span>
        </div>
    <?php endif; ?>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Resultado</div>
            <h2>Documentos contra usuario</h2>
            <p>
                Permitido significa que el usuario puede ver el documento. Bloqueado indica que la regla lo está deteniendo.
            </p>
        </div>
    </div>

    <div class="service-groups">
        <div class="service-board">
            <div class="service-list">
                <?php if (!$documents): ?>
                    <div class="service-item">
                        <span class="service-copy">
                            <strong>No hay documentos registrados</strong>
                            <span>Primero debe existir información en la tabla documents.</span>
                        </span>
                    </div>
                <?php endif; ?>

                <?php foreach ($documents as $document): ?>
                    <?php
                    $documentId = (int) ($document['id'] ?? 0);
                    $documentTitle = document_access_list_document_title($document);
                    $classification = (string) ($document['classification_name'] ?? 'Interno');
                    $department = trim((string) ($document['owner_department_name'] ?? ''));
                    $area = trim((string) ($document['owner_area_name'] ?? ''));
                    $scope = $department !== '' ? $department : ($area !== '' ? $area : 'General');
                    $decision = document_access_decision($documentId, $selectedUserId);
                    $allowed = (bool) ($decision['allowed'] ?? false);
                    $reason = (string) ($decision['reason'] ?? 'Sin detalle.');
                    ?>

                    <div class="service-item">
                        <span class="service-ico service-ico--blue" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M18 12h24l6 6v34H18z"></path>
                                <path d="M42 12v8h8"></path>
                                <path d="M24 30h16"></path>
                                <path d="M24 38h16"></path>
                                <path d="M24 46h10"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong><?= e($documentTitle) ?></strong>

                            <span>
                                <?= e($reason) ?>
                            </span>

                            <span class="newsletter-meta">
                                <span class="tag tag--info">
                                    <?= e($classification) ?>
                                </span>

                                <span class="tag tag--ok">
                                    <?= e($scope) ?>
                                </span>

                                <span class="tag <?= $allowed ? 'tag--ok' : 'tag--danger' ?>">
                                    <?= $allowed ? 'Permitido' : 'Bloqueado' ?>
                                </span>
                            </span>
                        </span>

                        <span class="service-right">
                            <a class="tag tag--info" href="<?= e(base_url('admin/documents/access.php?document_id=' . $documentId)) ?>">
                                Ajustar acceso
                            </a>

                            <span class="service-arrow">›</span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php

require_once dirname(__DIR__, 2) . '/includes/footer.php';
