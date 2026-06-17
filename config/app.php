<?php

declare(strict_types=1);

date_default_timezone_set('America/Mexico_City');

$appConfig = [
    'environment' => 'local',
    'code_name' => 'Nexus',
    'name' => 'Portal interno',
    'full_name' => 'Wolk-It Portal interno',
    'company_name' => 'Wolk IT Services',
    'subtitle' => 'Sistema interno de accesos, solicitudes, documentos y recursos de Wolk IT',
    'timezone' => 'America/Mexico_City',
    'public_app_url' => 'https://portal.wolk-it.com',
    'public_base_path' => '',
    'base_url' => '/wolk-portal',
    'assets_url' => '/wolk-portal/assets',
    'root_path' => dirname(__DIR__),
    'config_path' => __DIR__,
    'storage_path' => dirname(__DIR__) . '/storage',
    'session_name' => 'wolk_nexus_session',
    'csrf_token_key' => 'wolk_nexus_csrf_token',
];

function app_config(string $key, mixed $default = null): mixed
{
    global $appConfig;

    return $appConfig[$key] ?? $default;
}

function app_environment(): string
{
    return (string) app_config('environment', 'local');
}

function is_production(): bool
{
    return app_environment() === 'production';
}

function app_visible_name(): string
{
    return (string) app_config('full_name', 'Wolk-It Portal interno');
}

function app_code_name(): string
{
    return (string) app_config('code_name', 'Nexus');
}

function base_url(string $path = ''): string
{
    $baseUrl = rtrim((string) app_config('base_url'), '/');
    $path = ltrim($path, '/');

    if ($path === '') {
        return $baseUrl === '' ? '/' : $baseUrl;
    }

    return ($baseUrl === '' ? '' : $baseUrl) . '/' . $path;
}

function asset_url(string $path = ''): string
{
    $assetsUrl = rtrim((string) app_config('assets_url'), '/');
    $path = ltrim($path, '/');

    if ($path === '') {
        return $assetsUrl;
    }

    return $assetsUrl . '/' . $path;
}

function app_url(string $path = ''): string
{
    $publicAppUrl = rtrim((string) app_config('public_app_url'), '/');
    $publicBasePath = trim((string) app_config('public_base_path', ''), '/');
    $path = trim($path, '/');

    $segments = array_filter([
        $publicBasePath,
        $path,
    ]);

    if (!$segments) {
        return $publicAppUrl;
    }

    return $publicAppUrl . '/' . implode('/', $segments);
}
