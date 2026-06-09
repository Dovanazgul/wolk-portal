<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/access-control.php';
require_once __DIR__ . '/../includes/document_access.php';

require_auth();
require_password_updated();
require_profile_confirmed();
require_access('documentos');

$currentUser = auth_user();

if (!$currentUser) {
    redirect('auth/login.php');
}

$systemName = app_visible_name();

$pageTitle = 'Documentos internos | ' . $systemName;
$pageDescription = 'Consulta documentos internos conforme a tu perfil de acceso.';

$query = trim((string) ($_GET['q'] ?? ''));
$typeFilter = trim((string) ($_GET['type'] ?? ''));

$currentRoles = document_access_user_roles((int) $currentUser['id']);
$isSuperScope = document_access_is_super_scope($currentRoles);

function docs_normalize_value(string $value): string
{
    $value = trim($value);
    $value = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'],
        $value
    );

    return strtoupper($value);
}

function docs_is_direction(array $roles): bool
{
    return in_array('DIRECCION', $roles, true);
}

function docs_user_override(int $documentId, int $userId): ?string
{
    $statement = db()->prepare("
        SELECT access_type
        FROM document_access_users
        WHERE document_id = :document_id
          AND user_id = :user_id
        ORDER BY FIELD(access_type, 'deny', 'allow')
        LIMIT 1
    ");

    $statement->execute([
        ':document_id' => $documentId,
        ':user_id' => $userId,
    ]);

    $accessType = $statement->fetchColumn();

    return $accessType ? (string) $accessType : null;
}

function docs_role_override(int $documentId, array $roles): ?string
{
    if (!$roles) {
        return null;
    }

    $placeholders = [];

    foreach ($roles as $index => $role) {
        $placeholders[] = ':role_' . $index;
    }

    $statement = db()->prepare("
        SELECT access_type
        FROM document_access_roles
        WHERE document_id = :document_id
          AND UPPER(role_name) IN (" . implode(', ', $placeholders) . ")
        ORDER BY FIELD(access_type, 'deny', 'allow')
        LIMIT 1
    ");

    $statement->bindValue(':document_id', $documentId, PDO::PARAM_INT);

    foreach ($roles as $index => $role) {
        $statement->bindValue(':role_' . $index, $role);
    }

    $statement->execute();

    $accessType = $statement->fetchColumn();

    return $accessType ? (string) $accessType : null;
}

function docs_same_scope(array $document, array $user): bool
{
    $documentAreaId = (int) ($document['owner_area_id'] ?? 0);
    $documentDepartmentId = (int) ($document['owner_department_id'] ?? 0);

    $userAreaId = (int) ($user['area_id'] ?? 0);
    $userDepartmentId = (int) ($user['department_id'] ?? 0);

    $documentAreaName = docs_normalize_value((string) ($document['owner_area_name'] ?? ''));
    $documentDepartmentName = docs_normalize_value((string) ($document['owner_department_name'] ?? ''));

    $userAreaName = docs_normalize_value((string) ($user['area_name'] ?? ''));
    $userDepartmentName = docs_normalize_value((string) ($user['department_name'] ?? ''));

    return ($documentAreaId > 0 && $userAreaId > 0 && $documentAreaId === $userAreaId)
        || ($documentDepartmentId > 0 && $userDepartmentId > 0 && $documentDepartmentId === $userDepartmentId)
        || ($documentAreaName !== '' && $userAreaName !== '' && $documentAreaName === $userAreaName)
        || ($documentDepartmentName !== '' && $userDepartmentName !== '' && $documentDepartmentName === $userDepartmentName);
}

function docs_has_scope(array $document): bool
{
    return (int) ($document['owner_area_id'] ?? 0) > 0
        || (int) ($document['owner_department_id'] ?? 0) > 0
        || trim((string) ($document['owner_area_name'] ?? '')) !== ''
        || trim((string) ($document['owner_department_name'] ?? '')) !== '';
}

function docs_can_view(array $document, array $user, array $roles): bool
{
    $documentId = (int) ($document['id'] ?? 0);
    $userId = (int) ($user['id'] ?? 0);

    if ($documentId <= 0 || $userId <= 0) {
        return false;
    }

    if (document_access_is_super_scope($roles) || docs_is_direction($roles)) {
        return true;
    }

    $userOverride = docs_user_override($documentId, $userId);

    if ($userOverride === 'deny') {
        return false;
    }

    $roleOverride = docs_role_override($documentId, $roles);

    if ($roleOverride === 'deny') {
        return false;
    }

    if ($userOverride === 'allow' || $roleOverride === 'allow') {
        return true;
    }

    $classification = strtolower((string) ($document['classification_code'] ?? 'internal'));
    $hasScope = docs_has_scope($document);
    $sameScope = docs_same_scope($document, $user);

    if ($classification === 'public_internal') {
        return true;
    }

    if ($classification === 'internal') {
        return !$hasScope || $sameScope;
    }

    if (in_array($classification, ['departmental', 'confidential'], true)) {
        return $sameScope;
    }

    return false;
}

function docs_visible_documents(array $user, array $roles, string $query = '', string $type = ''): array
{
    $where = ['COALESCE(d.is_published, 1) = 1'];
    $params = [];

    if ($query !== '') {
        $where[] = "(
            d.title LIKE :query
            OR d.document_code LIKE :query
            OR d.description LIKE :query
            OR d.version LIKE :query
            OR ds.name LIKE :query
            OR dr.requirement_code LIKE :query
            OR dr.requirement_title LIKE :query
            OR dp.name LIKE :query
        )";

        $params[':query'] = '%' . $query . '%';
    }

    if ($type === 'iso_27001') {
        $where[] = "ds.code = 'ISO_27001'";
    }

    if ($type === 'iso_20000') {
        $where[] = "ds.code = 'ISO_20000'";
    }

    if ($type === 'projects') {
        $where[] = "(d.registry_section = 'projects' OR d.project_id IS NOT NULL)";
    }

    $statement = db()->prepare("
        SELECT
            d.*,
            dc.name AS classification_name,
            dc.code AS classification_code,
            a.name AS owner_area_name,
            dep.name AS owner_department_name,
            ds.name AS standard_name,
            ds.code AS standard_code,
            dr.requirement_code,
            dr.requirement_title,
            dp.name AS project_name,
            approved_user.full_name AS approved_by_name
        FROM documents d
        LEFT JOIN document_classifications dc
            ON dc.id = d.classification_id
        LEFT JOIN areas a
            ON a.id = d.owner_area_id
        LEFT JOIN departments dep
            ON dep.id = d.owner_department_id
        LEFT JOIN document_standards ds
            ON ds.id = d.standard_id
        LEFT JOIN document_requirements dr
            ON dr.id = d.requirement_id
        LEFT JOIN document_projects dp
            ON dp.id = d.project_id
        LEFT JOIN users approved_user
            ON approved_user.id = d.approved_by_user_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY
            COALESCE(d.valid_until, '2999-12-31') ASC,
            d.id DESC
    ");

    $statement->execute($params);

    $documents = $statement->fetchAll();
    $visible = [];

    foreach ($documents as $document) {
        if (docs_can_view($document, $user, $roles)) {
            $visible[] = $document;
        }
    }

    return $visible;
}

function docs_count_by_type(array $documents, string $type): int
{
    $count = 0;

    foreach ($documents as $document) {
        $standardCode = strtoupper((string) ($document['standard_code'] ?? ''));
        $section = strtolower((string) ($document['registry_section'] ?? ''));

        if ($type === 'iso_27001' && $standardCode === 'ISO_27001') {
            $count++;
        }

        if ($type === 'iso_20000' && $standardCode === 'ISO_20000') {
            $count++;
        }

        if ($type === 'projects' && ($section === 'projects' || !empty($document['project_id']))) {
            $count++;
        }
    }

    return $count;
}

function docs_progress(int $current, int $expected): int
{
    if ($expected <= 0) {
        return 0;
    }

    return min(100, (int) round(($current / $expected) * 100));
}

function docs_standard_image_src(string $type): string
{
    if ($type === 'projects') {
        $svg = '
            <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
                <rect width="120" height="120" rx="28" fill="#ecfeff"/>
                <path d="M26 38c0-6 5-11 11-11h22l9 10h15c6 0 11 5 11 11v34c0 6-5 11-11 11H37c-6 0-11-5-11-11V38z" fill="#ffffff" stroke="#0ea5a8" stroke-width="5" stroke-linejoin="round"/>
                <path d="M35 53h50" stroke="#94a3b8" stroke-width="4" stroke-linecap="round"/>
                <circle cx="44" cy="72" r="6" fill="#37b24d"/>
                <circle cx="60" cy="72" r="6" fill="#0ea5a8"/>
                <circle cx="76" cy="72" r="6" fill="#2563eb"/>
                <path d="M43 85h34" stroke="#0f172a" stroke-width="4" stroke-linecap="round" opacity=".45"/>
            </svg>
        ';

        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }

    $isIso20000 = $type === 'iso_20000';
    $number = $isIso20000 ? '20000-1' : '27001';
    $main = $isIso20000 ? '#0f5f9f' : '#0ea5a8';
    $soft = $isIso20000 ? '#e0f2fe' : '#ecfdf5';

    $svg = '
        <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
            <rect width="120" height="120" rx="28" fill="' . $soft . '"/>
            <circle cx="60" cy="60" r="38" fill="#ffffff" stroke="' . $main . '" stroke-width="5"/>
            <circle cx="60" cy="60" r="49" fill="none" stroke="' . $main . '" stroke-width="3" stroke-dasharray="8 6" opacity=".75"/>
            <path d="M37 60h46" stroke="' . $main . '" stroke-width="3" stroke-linecap="round" opacity=".35"/>
            <text x="60" y="54" text-anchor="middle" font-family="Arial" font-size="25" font-weight="900" fill="' . $main . '">ISO</text>
            <text x="60" y="76" text-anchor="middle" font-family="Arial" font-size="15" font-weight="900" fill="#0f172a">' . $number . '</text>
        </svg>
    ';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function docs_document_title(array $document): string
{
    if (!empty($document['title'])) {
        return (string) $document['title'];
    }

    if (!empty($document['document_code'])) {
        return (string) $document['document_code'];
    }

    return 'Documento interno';
}

function docs_document_description(array $document): string
{
    return !empty($document['description'])
        ? (string) $document['description']
        : 'Documento disponible para consulta interna.';
}

function docs_document_url(array $document): string
{
    $documentId = (int) ($document['id'] ?? 0);

    if (!empty($document['link_url'])) {
        return (string) $document['link_url'];
    }

    if (!empty($document['url'])) {
        return (string) $document['url'];
    }

    if (!empty($document['drive_url'])) {
        return (string) $document['drive_url'];
    }

    return base_url('documentos/view.php?id=' . $documentId);
}

function docs_type_label(array $document): string
{
    $standardCode = strtoupper((string) ($document['standard_code'] ?? ''));
    $projectName = trim((string) ($document['project_name'] ?? ''));

    if ($standardCode === 'ISO_27001') {
        return 'ISO 27001';
    }

    if ($standardCode === 'ISO_20000') {
        return 'ISO 20000';
    }

    if ($projectName !== '') {
        return 'Proyecto: ' . $projectName;
    }

    return 'Documento';
}

function docs_scope_label(array $document): string
{
    $department = trim((string) ($document['owner_department_name'] ?? ''));
    $area = trim((string) ($document['owner_area_name'] ?? ''));

    if ($department !== '') {
        return $department;
    }

    if ($area !== '') {
        return $area;
    }

    return 'General';
}

function docs_validity_label(array $document): string
{
    $validUntil = trim((string) ($document['valid_until'] ?? ''));

    return $validUntil !== '' ? 'Vence: ' . $validUntil : 'Sin vigencia';
}

function docs_requirement_label(array $document): string
{
    $code = trim((string) ($document['requirement_code'] ?? ''));
    $title = trim((string) ($document['requirement_title'] ?? ''));

    if ($code !== '' && $title !== '') {
        return $code . ' · ' . $title;
    }

    return $code !== '' ? $code : '';
}

$allVisibleDocuments = docs_visible_documents($currentUser, $currentRoles);
$visibleDocuments = docs_visible_documents($currentUser, $currentRoles, $query, $typeFilter);

$iso27001Expected = 6;
$iso20000Expected = 1;

$iso27001Count = docs_count_by_type($allVisibleDocuments, 'iso_27001');
$iso20000Count = docs_count_by_type($allVisibleDocuments, 'iso_20000');
$projectsCount = docs_count_by_type($allVisibleDocuments, 'projects');

$iso27001Progress = docs_progress($iso27001Count, $iso27001Expected);
$iso20000Progress = docs_progress($iso20000Count, $iso20000Expected);

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<section class="intro-block">
    <div class="eyebrow">Documentos internos</div>

    <h1>Consulta documental</h1>

    <p>
        Busca y consulta los documentos disponibles para tu usuario, área y departamento.
    </p>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Búsqueda</div>
            <h2>Buscar documentos</h2>
            <p>
                Filtra por palabra clave, ISO o documentos de proyectos.
            </p>
        </div>

        <span class="pill">
            <?= e((string) count($visibleDocuments)) ?> visibles
        </span>
    </div>

    <form method="GET" class="top-grid" autocomplete="off">
        <div class="form-group">
            <label for="q">Buscar documento</label>
            <input
                class="form-control"
                type="search"
                id="q"
                name="q"
                value="<?= e($query) ?>"
                placeholder="Nombre, código, requisito o proyecto...">
        </div>

        <div class="form-group">
            <label for="type">Tipo</label>
            <select class="form-control" id="type" name="type">
                <option value="">Todos</option>
                <option value="iso_27001" <?= $typeFilter === 'iso_27001' ? 'selected' : '' ?>>ISO 27001</option>
                <option value="iso_20000" <?= $typeFilter === 'iso_20000' ? 'selected' : '' ?>>ISO 20000</option>
                <option value="projects" <?= $typeFilter === 'projects' ? 'selected' : '' ?>>Proyectos</option>
            </select>
        </div>

        <div class="form-group">
            <label>&nbsp;</label>
            <button class="btn btn--primary" type="submit">
                Buscar
            </button>
        </div>
    </form>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Resultado</div>
            <h2>Documentos para consultar</h2>
            <p>
                Solo se muestran documentos permitidos para tu perfil.
            </p>
        </div>
    </div>

    <div class="service-groups">
        <div class="service-board">
            <div class="service-list">
                <?php if (!$visibleDocuments): ?>
                    <div class="service-item">
                        <span class="service-copy">
                            <strong>Sin documentos visibles</strong>
                            <span>No hay documentos disponibles con los filtros seleccionados o para tu perfil actual.</span>
                        </span>
                    </div>
                <?php endif; ?>

                <?php foreach ($visibleDocuments as $document): ?>
                    <?php
                    $documentId = (int) ($document['id'] ?? 0);
                    $title = docs_document_title($document);
                    $description = docs_document_description($document);
                    $url = docs_document_url($document);
                    $isExternalUrl = str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
                    $typeLabel = docs_type_label($document);
                    $classification = (string) ($document['classification_name'] ?? 'Interno');
                    $scope = docs_scope_label($document);
                    $validity = docs_validity_label($document);
                    $requirement = docs_requirement_label($document);
                    ?>

                    <div class="service-item">
                        <span class="service-copy">
                            <strong>
                                <a href="<?= e($url) ?>" <?= $isExternalUrl ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                                    <?= e($title) ?>
                                </a>
                            </strong>

                            <span><?= e($description) ?></span>

                            <span class="newsletter-meta">
                                <span class="tag tag--info"><?= e($typeLabel) ?></span>
                                <span class="tag tag--info"><?= e($classification) ?></span>
                                <span class="tag tag--ok"><?= e($scope) ?></span>
                                <span class="tag tag--info"><?= e($validity) ?></span>

                                <?php if ($requirement !== ''): ?>
                                    <span class="tag tag--info"><?= e($requirement) ?></span>
                                <?php endif; ?>
                            </span>
                        </span>

                        <span class="service-right">
                            <?php if ($isSuperScope): ?>
                                <a class="tag tag--info" href="<?= e(base_url('admin/documents/access.php?document_id=' . $documentId)) ?>">
                                    Ajustar acceso
                                </a>
                            <?php endif; ?>

                            <a class="tag tag--info" href="<?= e($url) ?>" <?= $isExternalUrl ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                                Consultar
                            </a>

                            <span class="service-arrow">›</span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($isSuperScope): ?>
    <section class="card section doc-implementation">
        <div class="section-head">
            <div>
                <div class="eyebrow">Implementación</div>
                <h2>Estándares y proyectos</h2>
                <p>
                    Selecciona una sección para revisar su lista maestra de documentos y avance.
                </p>
            </div>

            <div class="hero-actions">
                <a class="btn btn--primary" href="<?= e(base_url('admin/documents/create.php')) ?>">
                    Nuevo documento
                </a>
            </div>
        </div>

        <div class="doc-standard-grid">
            <a class="doc-standard-card" href="<?= e(base_url('documentos?type=iso_27001')) ?>">
                <div class="doc-standard-card__top">
                    <strong>ISO 27001:2022</strong>
                    <span class="doc-standard-card__percent"><?= e((string) $iso27001Progress) ?>%</span>
                </div>

                <div class="doc-standard-card__body">
                    <img class="doc-standard-card__icon" src="<?= e(docs_standard_image_src('iso_27001')) ?>" alt="ISO 27001">
                    <p>Sistema de Gestión de Seguridad de la Información.</p>
                </div>

                <div class="doc-standard-card__meta">
                    <?= e((string) $iso27001Count) ?>/<?= e((string) $iso27001Expected) ?> documentos
                </div>

                <progress class="doc-standard-card__progress" value="<?= e((string) $iso27001Progress) ?>" max="100"></progress>
            </a>

            <a class="doc-standard-card" href="<?= e(base_url('documentos?type=iso_20000')) ?>">
                <div class="doc-standard-card__top">
                    <strong>ISO 20000-1</strong>
                    <span class="doc-standard-card__percent"><?= e((string) $iso20000Progress) ?>%</span>
                </div>

                <div class="doc-standard-card__body">
                    <img class="doc-standard-card__icon" src="<?= e(docs_standard_image_src('iso_20000')) ?>" alt="ISO 20000">
                    <p>Sistema de Gestión de Servicios ITSM.</p>
                </div>

                <div class="doc-standard-card__meta">
                    <?= e((string) $iso20000Count) ?>/<?= e((string) $iso20000Expected) ?> documentos
                </div>

                <progress class="doc-standard-card__progress" value="<?= e((string) $iso20000Progress) ?>" max="100"></progress>
            </a>

            <a class="doc-standard-card" href="<?= e(base_url('documentos?type=projects')) ?>">
                <div class="doc-standard-card__top">
                    <strong>Proyectos</strong>
                    <span class="doc-standard-card__percent"><?= e((string) $projectsCount) ?></span>
                </div>

                <div class="doc-standard-card__body">
                    <img class="doc-standard-card__icon" src="<?= e(docs_standard_image_src('projects')) ?>" alt="Proyectos">
                    <p>Documentos organizados por proyecto, cliente o entrega interna.</p>
                </div>

                <div class="doc-standard-card__meta">
                    <?= e((string) $projectsCount) ?> documentos
                </div>

                <progress class="doc-standard-card__progress" value="<?= e((string) min(100, $projectsCount * 10)) ?>" max="100"></progress>
            </a>
        </div>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/../includes/footer.php';
