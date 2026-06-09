<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/document_access.php';

if (!function_exists('document_access_list_columns')) {
    function document_access_list_columns(): array
    {
        $statement = db()->query("DESCRIBE documents");
        $columns = [];

        foreach ($statement->fetchAll() as $column) {
            $columns[] = (string) $column['Field'];
        }

        return $columns;
    }
}

if (!function_exists('document_access_list_order_column')) {
    function document_access_list_order_column(array $columns): string
    {
        if (in_array('created_at', $columns, true)) {
            return 'created_at';
        }

        if (in_array('uploaded_at', $columns, true)) {
            return 'uploaded_at';
        }

        if (in_array('updated_at', $columns, true)) {
            return 'updated_at';
        }

        return 'id';
    }
}

if (!function_exists('document_access_list_visible_documents')) {
    function document_access_list_visible_documents(?int $userId = null): array
    {
        $currentUser = auth_user();

        if ($userId === null && $currentUser) {
            $userId = (int) $currentUser['id'];
        }

        if (!$userId) {
            return [];
        }

        $columns = document_access_list_columns();
        $orderColumn = document_access_list_order_column($columns);

        $statement = db()->query("
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
            ORDER BY d.{$orderColumn} DESC
        ");

        $documents = $statement->fetchAll();
        $visibleDocuments = [];

        foreach ($documents as $document) {
            $documentId = (int) ($document['id'] ?? 0);

            if ($documentId > 0 && document_access_can_view($documentId, $userId)) {
                $visibleDocuments[] = $document;
            }
        }

        return $visibleDocuments;
    }
}

if (!function_exists('document_access_list_count_visible_documents')) {
    function document_access_list_count_visible_documents(?int $userId = null): int
    {
        return count(document_access_list_visible_documents($userId));
    }
}

if (!function_exists('document_access_list_document_title')) {
    function document_access_list_document_title(array $document): string
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
            if (!empty($document[$column])) {
                return (string) $document[$column];
            }
        }

        return 'Documento interno';
    }
}

if (!function_exists('document_access_list_document_description')) {
    function document_access_list_document_description(array $document): string
    {
        $possibleColumns = [
            'description',
            'summary',
            'details',
            'notes',
        ];

        foreach ($possibleColumns as $column) {
            if (!empty($document[$column])) {
                return (string) $document[$column];
            }
        }

        return 'Documento disponible para consulta interna.';
    }
}

if (!function_exists('document_access_list_document_url')) {
    function document_access_list_document_url(array $document): string
    {
        $documentId = (int) ($document['id'] ?? 0);

        if (!empty($document['url'])) {
            return (string) $document['url'];
        }

        if (!empty($document['link'])) {
            return (string) $document['link'];
        }

        if (!empty($document['file_url'])) {
            return (string) $document['file_url'];
        }

        if (!empty($document['drive_url'])) {
            return (string) $document['drive_url'];
        }

        return base_url('documentos/view.php?id=' . $documentId);
    }
}

if (!function_exists('document_access_list_classification_label')) {
    function document_access_list_classification_label(array $document): string
    {
        return (string) ($document['classification_name'] ?? 'Interno');
    }
}

if (!function_exists('document_access_list_scope_label')) {
    function document_access_list_scope_label(array $document): string
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
}
