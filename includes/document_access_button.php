<?php

declare(strict_types=1);

require_once __DIR__ . '/document_access_admin.php';

if (!function_exists('document_access_button_can_show')) {
    function document_access_button_can_show(): bool
    {
        return document_access_admin_can_manage();
    }
}

if (!function_exists('document_access_button_url')) {
    function document_access_button_url(int $documentId): string
    {
        return base_url('admin/documents/access.php?document_id=' . $documentId);
    }
}

if (!function_exists('document_access_button')) {
    function document_access_button(int $documentId, string $label = 'Ajustar acceso'): string
    {
        if ($documentId <= 0 || !document_access_button_can_show()) {
            return '';
        }

        return '
            <a class="btn btn--ghost" href="' . e(document_access_button_url($documentId)) . '">
                ' . e($label) . '
            </a>
        ';
    }
}

if (!function_exists('document_access_button_small')) {
    function document_access_button_small(int $documentId, string $label = 'Ajustar acceso'): string
    {
        if ($documentId <= 0 || !document_access_button_can_show()) {
            return '';
        }

        return '
            <a class="tag tag--info" href="' . e(document_access_button_url($documentId)) . '">
                ' . e($label) . '
            </a>
        ';
    }
}
