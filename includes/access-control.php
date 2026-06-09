<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function access_user_context(array|null $user = null): array
{
    $user = $user ?? auth_user();

    if (!$user) {
        return [
            'user_id' => null,
            'roles' => [],
            'area_id' => null,
            'area_slug' => null,
            'primary_department_id' => null,
            'primary_department_slug' => null,
            'department_ids' => [],
            'department_slugs' => [],
        ];
    }

    $userId = (int) $user['id'];

    $statement = db()->prepare("
        SELECT
            u.area_id,
            u.primary_department_id,
            a.slug AS area_slug,
            d.slug AS primary_department_slug
        FROM users u
        LEFT JOIN areas a ON a.id = u.area_id
        LEFT JOIN departments d ON d.id = u.primary_department_id
        WHERE u.id = :id
        LIMIT 1
    ");

    $statement->execute([
        ':id' => $userId,
    ]);

    $profile = $statement->fetch() ?: [];

    $departmentsStatement = db()->prepare("
        SELECT
            d.id,
            d.slug
        FROM user_departments ud
        INNER JOIN departments d ON d.id = ud.department_id
        WHERE ud.user_id = :user_id
        ORDER BY ud.is_primary DESC, d.name ASC
    ");

    $departmentsStatement->execute([
        ':user_id' => $userId,
    ]);

    $departments = $departmentsStatement->fetchAll();

    return [
        'user_id' => $userId,
        'roles' => auth_roles($userId),
        'area_id' => isset($profile['area_id']) ? (int) $profile['area_id'] : null,
        'area_slug' => isset($profile['area_slug']) ? strtolower((string) $profile['area_slug']) : null,
        'primary_department_id' => isset($profile['primary_department_id']) ? (int) $profile['primary_department_id'] : null,
        'primary_department_slug' => isset($profile['primary_department_slug']) ? strtolower((string) $profile['primary_department_slug']) : null,
        'department_ids' => array_map('intval', array_column($departments, 'id')),
        'department_slugs' => array_map('strtolower', array_column($departments, 'slug')),
    ];
}

function access_rules(): array
{
    return [
        'centro-mando' => [
            'roles' => [],
            'areas' => [],
            'departments' => [],
            'auth' => false,
        ],

        'perfil' => [
            'roles' => [],
            'areas' => [],
            'departments' => [],
            'auth' => true,
        ],

        'documentos' => [
            'roles' => [
                'superadmin',
                'ciso',
                'cto',
                'admin',
                'ceo',
                'direccion',
                'operaciones',
                'comercial',
                'administracion',
                'gerente',
            ],
            'areas' => [],
            'departments' => [],
            'auth' => true,
        ],

        'solicitudes' => [
            'roles' => [
                'superadmin',
                'ciso',
                'cto',
                'admin',
                'ceo',
                'direccion',
                'operaciones',
                'comercial',
                'administracion',
                'gerente',
            ],
            'areas' => [],
            'departments' => [],
            'auth' => true,
        ],

        'directorio' => [
            'roles' => [
                'superadmin',
                'ciso',
                'cto',
                'admin',
                'ceo',
                'direccion',
                'operaciones',
                'comercial',
                'administracion',
                'gerente',
            ],
            'areas' => [],
            'departments' => [],
            'auth' => true,
        ],

        'admin' => [
            'roles' => [
                'superadmin',
                'ciso',
                'cto',
                'admin',
                'ceo',
                'direccion',
            ],
            'areas' => [],
            'departments' => [],
            'auth' => true,
        ],

        'admin-critico' => [
            'roles' => [
                'superadmin',
                'ciso',
                'cto',
            ],
            'areas' => [],
            'departments' => [],
            'auth' => true,
        ],

        'operaciones' => [
            'roles' => [
                'superadmin',
                'ciso',
                'cto',
                'admin',
                'ceo',
                'direccion',
                'operaciones',
            ],
            'areas' => [
                'operaciones',
            ],
            'departments' => [
                'operaciones',
            ],
            'auth' => true,
        ],

        'comercial' => [
            'roles' => [
                'superadmin',
                'ciso',
                'cto',
                'admin',
                'ceo',
                'direccion',
                'comercial',
            ],
            'areas' => [
                'comercial',
            ],
            'departments' => [
                'comercial',
            ],
            'auth' => true,
        ],

        'administracion' => [
            'roles' => [
                'superadmin',
                'ciso',
                'cto',
                'admin',
                'ceo',
                'direccion',
                'administracion',
            ],
            'areas' => [
                'administracion',
            ],
            'departments' => [
                'administracion',
            ],
            'auth' => true,
        ],
    ];
}

function access_can(string $moduleSlug, array|null $user = null): bool
{
    $rules = access_rules();
    $moduleSlug = strtolower(trim($moduleSlug));

    if (!isset($rules[$moduleSlug])) {
        return false;
    }

    $rule = $rules[$moduleSlug];

    if (!($rule['auth'] ?? true)) {
        return true;
    }

    $context = access_user_context($user);

    if (!$context['user_id']) {
        return false;
    }

    $roles = array_map('strtolower', $context['roles']);
    $areas = array_filter([
        $context['area_slug'],
    ]);

    $departments = array_filter(array_merge(
        [$context['primary_department_slug']],
        $context['department_slugs']
    ));

    $allowedRoles = array_map('strtolower', $rule['roles'] ?? []);
    $allowedAreas = array_map('strtolower', $rule['areas'] ?? []);
    $allowedDepartments = array_map('strtolower', $rule['departments'] ?? []);

    if (count(array_intersect($roles, ['superadmin', 'ciso', 'cto'])) > 0) {
        return true;
    }

    if ($allowedRoles && count(array_intersect($roles, $allowedRoles)) > 0) {
        return true;
    }

    if ($allowedAreas && count(array_intersect($areas, $allowedAreas)) > 0) {
        return true;
    }

    if ($allowedDepartments && count(array_intersect($departments, $allowedDepartments)) > 0) {
        return true;
    }

    return !$allowedRoles && !$allowedAreas && !$allowedDepartments;
}

function require_access(string $moduleSlug): void
{
    if (!access_can($moduleSlug)) {
        http_response_code(403);
        exit('No tienes permiso para entrar a esta sección.');
    }
}
