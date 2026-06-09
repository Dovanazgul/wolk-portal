<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name((string) app_config('session_name', 'wolk_nexus_session'));
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function current_path(): string
{
    $requestUri = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?');

    return rtrim($requestUri, '/') ?: '/';
}

function csrf_token(): string
{
    $key = (string) app_config('csrf_token_key', 'wolk_nexus_csrf_token');

    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION[$key];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $key = (string) app_config('csrf_token_key', 'wolk_nexus_csrf_token');
    $sessionToken = (string) ($_SESSION[$key] ?? '');
    $postedToken = (string) ($_POST['_csrf'] ?? '');

    if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(419);
        exit('La sesión expiró o la solicitud no es válida. Vuelve a intentarlo.');
    }
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}
