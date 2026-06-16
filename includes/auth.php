<?php

declare(strict_types=1);

function auth_user(): array|null
{
    if (empty($_SESSION['auth_user_id'])) {
        return null;
    }

    $pdo = db();

    $statement = $pdo->prepare("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.email_verified_at,
            u.position_name,
            u.status,
            u.must_change_password,
            u.password_changed_at,
            u.profile_confirmed_at,
            u.profile_photo_url,
            u.google_workspace_id,
            u.google_photo_synced_at,
            u.login_provider,
            a.name AS area_name,
            d.name AS department_name
        FROM users u
        LEFT JOIN areas a ON a.id = u.area_id
        LEFT JOIN departments d ON d.id = u.primary_department_id
        WHERE u.id = :id
        AND u.status = 'activo'
        LIMIT 1
    ");

    $statement->execute([
        ':id' => $_SESSION['auth_user_id'],
    ]);

    $user = $statement->fetch();

    return $user ?: null;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function auth_roles(int $userId): array
{
    $statement = db()->prepare("
        SELECT r.slug
        FROM user_roles ur
        INNER JOIN roles r ON r.id = ur.role_id
        WHERE ur.user_id = :user_id
        ORDER BY r.name ASC
    ");

    $statement->execute([
        ':user_id' => $userId,
    ]);

    return array_map('strtolower', array_column($statement->fetchAll(), 'slug'));
}

function auth_has_role(string $roleSlug): bool
{
    $user = auth_user();

    if (!$user) {
        return false;
    }

    return in_array(strtolower($roleSlug), auth_roles((int) $user['id']), true);
}

function auth_has_any_role(array $roleSlugs): bool
{
    $user = auth_user();

    if (!$user) {
        return false;
    }

    $userRoles = auth_roles((int) $user['id']);
    $roleSlugs = array_map('strtolower', $roleSlugs);

    return count(array_intersect($userRoles, $roleSlugs)) > 0;
}

function auth_is_superadmin(): bool
{
    return auth_has_any_role([
        'superadmin',
        'ciso',
        'cto',
    ]);
}

function auth_is_admin(): bool
{
    return auth_is_superadmin() || auth_has_any_role([
        'admin',
        'ceo',
        'direccion',
    ]);
}

function auth_can_manage_users(): bool
{
    return auth_is_admin();
}

function auth_can_view_access(): bool
{
    return auth_is_admin();
}

function auth_can_view_structure(): bool
{
    return auth_is_superadmin();
}

function auth_can_view_security_events(): bool
{
    return auth_is_superadmin();
}

function auth_can_view_settings(): bool
{
    return auth_is_superadmin();
}

function auth_photo_url(array|null $user = null): string
{
    $user = $user ?? auth_user();

    if ($user && !empty($user['profile_photo_url'])) {
        return (string) $user['profile_photo_url'];
    }

    return asset_url('img/wolk_it_services_logo.jpeg');
}

function auth_must_change_password(array|null $user = null): bool
{
    $user = $user ?? auth_user();

    if (!$user) {
        return false;
    }

    return (int) ($user['must_change_password'] ?? 0) === 1;
}

function auth_profile_needs_confirmation(array|null $user = null): bool
{
    $user = $user ?? auth_user();

    if (!$user) {
        return false;
    }

    return empty($user['profile_confirmed_at']);
}

function auth_log_event(
    string $eventType,
    int|null $userId = null,
    string|null $email = null
): void {
    try {
        $statement = db()->prepare("
            INSERT INTO login_events (
                user_id,
                email,
                event_type,
                ip_address,
                user_agent
            ) VALUES (
                :user_id,
                :email,
                :event_type,
                :ip_address,
                :user_agent
            )
        ");

        $statement->execute([
            ':user_id' => $userId,
            ':email' => $email,
            ':event_type' => $eventType,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (Throwable $exception) {
        return;
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['auth_user_id'] = (int) $user['id'];
    $_SESSION['auth_user_name'] = (string) $user['full_name'];
    $_SESSION['auth_user_email'] = (string) $user['email'];

    auth_log_event(
        'login_success',
        (int) $user['id'],
        (string) $user['email']
    );
}

function logout_user(): void
{
    $user = auth_user();

    if ($user) {
        auth_log_event(
            'logout',
            (int) $user['id'],
            (string) $user['email']
        );
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function require_auth(): void
{
    if (!auth_check()) {
        redirect('auth/login.php');
    }
}

function guest_only(): void
{
    if (auth_check()) {
        redirect('/');
    }
}

function auth_redirect_after_login(): never
{
    $user = auth_user();

    if (!$user) {
        redirect('auth/login.php');
    }

    if (auth_must_change_password($user)) {
        redirect('profile/password');
    }

    if (auth_profile_needs_confirmation($user)) {
        redirect('profile/confirm');
    }

    redirect('/');
}

function require_password_updated(): void
{
    $user = auth_user();

    if (!$user) {
        redirect('auth/login.php');
    }

    $currentPath = current_path();

    if (
        auth_must_change_password($user)
        && !str_contains($currentPath, '/profile/password')
        && !str_contains($currentPath, '/auth/logout.php')
    ) {
        redirect('profile/password');
    }
}

function require_profile_confirmed(): void
{
    $user = auth_user();

    if (!$user) {
        redirect('auth/login.php');
    }

    $currentPath = current_path();

    if (
        !auth_must_change_password($user)
        && auth_profile_needs_confirmation($user)
        && !str_contains($currentPath, '/profile/confirm')
        && !str_contains($currentPath, '/profile/password')
        && !str_contains($currentPath, '/auth/logout.php')
    ) {
        redirect('profile/confirm');
    }
}
