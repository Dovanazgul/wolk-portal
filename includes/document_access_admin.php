<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/document_access.php';

if (!function_exists('document_access_admin_roles')) {
    function document_access_admin_roles(): array
    {
        return [
            'SUPERADMIN',
            'CISO',
            'CTO',
            'DIRECCION',
            'ADMIN',
            'OPERACIONES',
            'COMERCIAL',
            'ADMINISTRACION',
            'GERENTE',
        ];
    }
}

if (!function_exists('document_access_admin_table_columns')) {
    function document_access_admin_table_columns(string $tableName): array
    {
        $statement = db()->query("DESCRIBE {$tableName}");
        $columns = [];

        foreach ($statement->fetchAll() as $column) {
            $columns[] = (string) $column['Field'];
        }

        return $columns;
    }
}

if (!function_exists('document_access_admin_column')) {
    function document_access_admin_column(array $columns, array $possibleColumns): ?string
    {
        foreach ($possibleColumns as $column) {
            if (in_array($column, $columns, true)) {
                return $column;
            }
        }

        return null;
    }
}

if (!function_exists('document_access_admin_can_manage')) {
    function document_access_admin_can_manage(): bool
    {
        $currentUser = auth_user();

        if (!$currentUser) {
            return false;
        }

        $roles = document_access_user_roles((int) $currentUser['id']);

        foreach ($roles as $role) {
            if (in_array($role, ['SUPERADMIN', 'CISO', 'CTO', 'DIRECCION', 'ADMIN'], true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('document_access_admin_require_manage')) {
    function document_access_admin_require_manage(): void
    {
        if (!document_access_admin_can_manage()) {
            http_response_code(403);
            exit('No tienes permiso para administrar accesos documentales.');
        }
    }
}

if (!function_exists('document_access_admin_classifications')) {
    function document_access_admin_classifications(): array
    {
        $statement = db()->query("
            SELECT
                id,
                code,
                name,
                description,
                level_order
            FROM document_classifications
            WHERE is_active = 1
            ORDER BY level_order ASC, name ASC
        ");

        return $statement->fetchAll();
    }
}

if (!function_exists('document_access_admin_areas')) {
    function document_access_admin_areas(): array
    {
        $statement = db()->query("
            SELECT
                id,
                name
            FROM areas
            ORDER BY name ASC
        ");

        return $statement->fetchAll();
    }
}

if (!function_exists('document_access_admin_departments')) {
    function document_access_admin_departments(): array
    {
        $statement = db()->query("
            SELECT
                id,
                name
            FROM departments
            ORDER BY name ASC
        ");

        return $statement->fetchAll();
    }
}

if (!function_exists('document_access_admin_users')) {
    function document_access_admin_users(): array
    {
        $columns = document_access_admin_table_columns('users');

        $nameColumn = document_access_admin_column($columns, ['full_name', 'name', 'display_name', 'nombre']);
        $emailColumn = document_access_admin_column($columns, ['email', 'correo']);
        $statusColumn = document_access_admin_column($columns, ['status', 'estatus', 'is_active']);
        $areaColumn = document_access_admin_column($columns, ['area_id']);
        $departmentColumn = document_access_admin_column($columns, ['department_id']);

        $nameSelect = $nameColumn ? "u.{$nameColumn} AS full_name" : "CONCAT('Usuario ', u.id) AS full_name";
        $emailSelect = $emailColumn ? "u.{$emailColumn} AS email" : "'' AS email";
        $statusSelect = $statusColumn ? "u.{$statusColumn} AS status" : "'activo' AS status";
        $areaSelect = $areaColumn ? "u.{$areaColumn} AS area_id" : "NULL AS area_id";
        $departmentSelect = $departmentColumn ? "u.{$departmentColumn} AS department_id" : "NULL AS department_id";

        $where = $statusColumn ? "WHERE u.{$statusColumn} IN ('activo', 'active', '1', 1)" : "";

        $statement = db()->query("
            SELECT
                u.id,
                {$nameSelect},
                {$emailSelect},
                {$areaSelect},
                {$departmentSelect},
                {$statusSelect}
            FROM users u
            {$where}
            ORDER BY full_name ASC
        ");

        return $statement->fetchAll();
    }
}

if (!function_exists('document_access_admin_get_role_rules')) {
    function document_access_admin_get_role_rules(int $documentId): array
    {
        $statement = db()->prepare("
            SELECT
                role_name,
                access_type
            FROM document_access_roles
            WHERE document_id = :document_id
            ORDER BY access_type ASC, role_name ASC
        ");

        $statement->execute([
            ':document_id' => $documentId,
        ]);

        return $statement->fetchAll();
    }
}

if (!function_exists('document_access_admin_get_user_rules')) {
    function document_access_admin_get_user_rules(int $documentId): array
    {
        $statement = db()->prepare("
            SELECT
                dau.user_id,
                dau.access_type,
                u.full_name,
                u.email
            FROM document_access_users dau
            INNER JOIN users u
                ON u.id = dau.user_id
            WHERE dau.document_id = :document_id
            ORDER BY dau.access_type ASC, u.full_name ASC
        ");

        $statement->execute([
            ':document_id' => $documentId,
        ]);

        return $statement->fetchAll();
    }
}

if (!function_exists('document_access_admin_clear_manual_rules')) {
    function document_access_admin_clear_manual_rules(int $documentId): void
    {
        $deleteUsers = db()->prepare("
            DELETE FROM document_access_users
            WHERE document_id = :document_id
        ");

        $deleteUsers->execute([
            ':document_id' => $documentId,
        ]);

        $deleteRoles = db()->prepare("
            DELETE FROM document_access_roles
            WHERE document_id = :document_id
        ");

        $deleteRoles->execute([
            ':document_id' => $documentId,
        ]);
    }
}

if (!function_exists('document_access_admin_save_document_scope')) {
    function document_access_admin_save_document_scope(
        int $documentId,
        int $classificationId,
        ?int $ownerAreaId,
        ?int $ownerDepartmentId,
        bool $manualAccessEnabled
    ): void {
        $statement = db()->prepare("
            UPDATE documents
            SET
                classification_id = :classification_id,
                owner_area_id = :owner_area_id,
                owner_department_id = :owner_department_id,
                manual_access_enabled = :manual_access_enabled
            WHERE id = :document_id
        ");

        $statement->execute([
            ':classification_id' => $classificationId,
            ':owner_area_id' => $ownerAreaId ?: null,
            ':owner_department_id' => $ownerDepartmentId ?: null,
            ':manual_access_enabled' => $manualAccessEnabled ? 1 : 0,
            ':document_id' => $documentId,
        ]);
    }
}

if (!function_exists('document_access_admin_save_user_rule')) {
    function document_access_admin_save_user_rule(int $documentId, int $userId, string $accessType): void
    {
        if (!in_array($accessType, ['allow', 'deny'], true)) {
            return;
        }

        $statement = db()->prepare("
            INSERT INTO document_access_users (
                document_id,
                user_id,
                access_type
            ) VALUES (
                :document_id,
                :user_id,
                :access_type
            )
            ON DUPLICATE KEY UPDATE
                access_type = VALUES(access_type)
        ");

        $statement->execute([
            ':document_id' => $documentId,
            ':user_id' => $userId,
            ':access_type' => $accessType,
        ]);
    }
}

if (!function_exists('document_access_admin_save_role_rule')) {
    function document_access_admin_save_role_rule(int $documentId, string $roleName, string $accessType): void
    {
        if (!in_array($accessType, ['allow', 'deny'], true)) {
            return;
        }

        $roleName = document_access_normalize_role($roleName);

        if ($roleName === '') {
            return;
        }

        $statement = db()->prepare("
            INSERT INTO document_access_roles (
                document_id,
                role_name,
                access_type
            ) VALUES (
                :document_id,
                :role_name,
                :access_type
            )
            ON DUPLICATE KEY UPDATE
                access_type = VALUES(access_type)
        ");

        $statement->execute([
            ':document_id' => $documentId,
            ':role_name' => $roleName,
            ':access_type' => $accessType,
        ]);
    }
}

if (!function_exists('document_access_admin_save_manual_access')) {
    function document_access_admin_save_manual_access(
        int $documentId,
        array $allowedUserIds,
        array $deniedUserIds,
        array $allowedRoles,
        array $deniedRoles
    ): void {
        document_access_admin_clear_manual_rules($documentId);

        foreach ($allowedUserIds as $userId) {
            $userId = (int) $userId;

            if ($userId > 0) {
                document_access_admin_save_user_rule($documentId, $userId, 'allow');
            }
        }

        foreach ($deniedUserIds as $userId) {
            $userId = (int) $userId;

            if ($userId > 0) {
                document_access_admin_save_user_rule($documentId, $userId, 'deny');
            }
        }

        foreach ($allowedRoles as $roleName) {
            document_access_admin_save_role_rule($documentId, (string) $roleName, 'allow');
        }

        foreach ($deniedRoles as $roleName) {
            document_access_admin_save_role_rule($documentId, (string) $roleName, 'deny');
        }
    }
}
