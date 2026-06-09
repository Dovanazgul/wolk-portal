<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (!function_exists('document_access_normalize_text')) {
    function document_access_normalize_text(string $value): string
    {
        $value = trim($value);
        $value = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'],
            $value
        );

        return strtoupper($value);
    }
}

if (!function_exists('document_access_normalize_role')) {
    function document_access_normalize_role(string $role): string
    {
        return document_access_normalize_text($role);
    }
}

if (!function_exists('document_access_user_roles')) {
    function document_access_user_roles(int $userId): array
    {
        if (!function_exists('auth_roles')) {
            return [];
        }

        $roles = auth_roles($userId);

        return array_values(array_unique(array_map(
            static fn($role) => document_access_normalize_role((string) $role),
            $roles
        )));
    }
}

if (!function_exists('document_access_is_super_scope')) {
    function document_access_is_super_scope(array $roles): bool
    {
        foreach ($roles as $role) {
            if (in_array($role, ['SUPERADMIN', 'CISO', 'CTO'], true)) {
                return true;
            }
        }

        return function_exists('auth_is_superadmin') && auth_is_superadmin();
    }
}

if (!function_exists('document_access_is_direction')) {
    function document_access_is_direction(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($role === 'DIRECCION') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('document_access_user')) {
    function document_access_user(int $userId): ?array
    {
        $statement = db()->prepare("
            SELECT
                id,
                status,
                area_id,
                department_id
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $statement->execute([
            ':id' => $userId,
        ]);

        $user = $statement->fetch();

        return $user ?: null;
    }
}

if (!function_exists('document_access_document')) {
    function document_access_document(int $documentId): ?array
    {
        $statement = db()->prepare("
            SELECT
                d.id,
                d.classification_id,
                d.owner_area_id,
                d.owner_department_id,
                d.manual_access_enabled,
                COALESCE(dc.code, 'internal') AS classification_code,
                COALESCE(dc.name, 'Interno') AS classification_name
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
}

if (!function_exists('document_access_user_override')) {
    function document_access_user_override(int $documentId, int $userId): ?string
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
}

if (!function_exists('document_access_role_override')) {
    function document_access_role_override(int $documentId, array $roles): ?string
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
}

if (!function_exists('document_access_has_document_scope')) {
    function document_access_has_document_scope(array $document): bool
    {
        return (int) ($document['owner_area_id'] ?? 0) > 0
            || (int) ($document['owner_department_id'] ?? 0) > 0;
    }
}

if (!function_exists('document_access_same_area')) {
    function document_access_same_area(array $user, array $document): bool
    {
        $userAreaId = (int) ($user['area_id'] ?? 0);
        $documentAreaId = (int) ($document['owner_area_id'] ?? 0);

        return $userAreaId > 0 && $documentAreaId > 0 && $userAreaId === $documentAreaId;
    }
}

if (!function_exists('document_access_same_department')) {
    function document_access_same_department(array $user, array $document): bool
    {
        $userDepartmentId = (int) ($user['department_id'] ?? 0);
        $documentDepartmentId = (int) ($document['owner_department_id'] ?? 0);

        return $userDepartmentId > 0 && $documentDepartmentId > 0 && $userDepartmentId === $documentDepartmentId;
    }
}

if (!function_exists('document_access_same_scope')) {
    function document_access_same_scope(array $user, array $document): bool
    {
        return document_access_same_department($user, $document)
            || document_access_same_area($user, $document);
    }
}

if (!function_exists('document_access_scope_reason')) {
    function document_access_scope_reason(array $user, array $document): string
    {
        if (document_access_same_department($user, $document)) {
            return 'Acceso por departamento correspondiente.';
        }

        if (document_access_same_area($user, $document)) {
            return 'Acceso por área correspondiente.';
        }

        return 'Documento limitado a otra área o departamento.';
    }
}

if (!function_exists('document_access_decision')) {
    function document_access_decision(int $documentId, ?int $userId = null): array
    {
        $currentUser = auth_user();

        if ($userId === null && $currentUser) {
            $userId = (int) $currentUser['id'];
        }

        if (!$userId) {
            return [
                'allowed' => false,
                'reason' => 'Usuario no autenticado.',
            ];
        }

        $user = document_access_user($userId);

        if (!$user || ($user['status'] ?? '') !== 'activo') {
            return [
                'allowed' => false,
                'reason' => 'Usuario sin acceso activo.',
            ];
        }

        $document = document_access_document($documentId);

        if (!$document) {
            return [
                'allowed' => false,
                'reason' => 'Documento no encontrado.',
            ];
        }

        $roles = document_access_user_roles($userId);

        if (document_access_is_super_scope($roles)) {
            return [
                'allowed' => true,
                'reason' => 'Acceso superior autorizado.',
            ];
        }

        if (document_access_is_direction($roles)) {
            return [
                'allowed' => true,
                'reason' => 'Acceso autorizado por Dirección.',
            ];
        }

        $userOverride = document_access_user_override($documentId, $userId);

        if ($userOverride === 'deny') {
            return [
                'allowed' => false,
                'reason' => 'Usuario bloqueado para este documento.',
            ];
        }

        $roleOverride = document_access_role_override($documentId, $roles);

        if ($roleOverride === 'deny') {
            return [
                'allowed' => false,
                'reason' => 'Rol bloqueado para este documento.',
            ];
        }

        if ($userOverride === 'allow') {
            return [
                'allowed' => true,
                'reason' => 'Usuario autorizado manualmente.',
            ];
        }

        if ($roleOverride === 'allow') {
            return [
                'allowed' => true,
                'reason' => 'Rol autorizado manualmente.',
            ];
        }

        $classificationCode = strtolower((string) ($document['classification_code'] ?? 'internal'));
        $hasScope = document_access_has_document_scope($document);
        $sameScope = document_access_same_scope($user, $document);

        if ($classificationCode === 'public_internal') {
            return [
                'allowed' => true,
                'reason' => 'Documento interno disponible.',
            ];
        }

        if ($classificationCode === 'internal') {
            if (!$hasScope) {
                return [
                    'allowed' => true,
                    'reason' => 'Documento interno disponible.',
                ];
            }

            return [
                'allowed' => $sameScope,
                'reason' => $sameScope
                    ? document_access_scope_reason($user, $document)
                    : 'Documento interno limitado al área o departamento asignado.',
            ];
        }

        if (in_array($classificationCode, ['departmental', 'confidential'], true)) {
            return [
                'allowed' => $sameScope,
                'reason' => $sameScope
                    ? document_access_scope_reason($user, $document)
                    : 'Documento limitado a otra área o departamento.',
            ];
        }

        if (in_array($classificationCode, ['restricted', 'critical'], true)) {
            return [
                'allowed' => false,
                'reason' => 'Documento requiere autorización específica.',
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Clasificación no reconocida.',
        ];
    }
}

if (!function_exists('document_access_can_view')) {
    function document_access_can_view(int $documentId, ?int $userId = null): bool
    {
        $decision = document_access_decision($documentId, $userId);

        return (bool) $decision['allowed'];
    }
}

if (!function_exists('document_access_log')) {
    function document_access_log(int $documentId, string $action, bool $allowed, ?string $reason = null, ?int $userId = null): void
    {
        $currentUser = auth_user();

        if ($userId === null && $currentUser) {
            $userId = (int) $currentUser['id'];
        }

        $statement = db()->prepare("
            INSERT INTO document_access_logs (
                document_id,
                user_id,
                action,
                result,
                reason,
                ip_address,
                user_agent
            ) VALUES (
                :document_id,
                :user_id,
                :action,
                :result,
                :reason,
                :ip_address,
                :user_agent
            )
        ");

        $statement->execute([
            ':document_id' => $documentId,
            ':user_id' => $userId,
            ':action' => $action,
            ':result' => $allowed ? 'allowed' : 'denied',
            ':reason' => $reason,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}

if (!function_exists('document_access_require_view')) {
    function document_access_require_view(int $documentId, string $action = 'view'): void
    {
        $decision = document_access_decision($documentId);

        document_access_log(
            $documentId,
            $action,
            (bool) $decision['allowed'],
            (string) $decision['reason']
        );

        if (!$decision['allowed']) {
            http_response_code(403);
            exit('No tienes permiso para consultar este documento.');
        }
    }
}
