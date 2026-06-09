<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$currentPath = current_path();
$currentUser = auth_user();
$currentRoles = $currentUser ? auth_roles((int) $currentUser['id']) : [];

$assetVersion = time();
$logoUrl = asset_url('img/wolk_it_services_logo.jpeg') . '?v=' . $assetVersion;
$profilePhotoUrl = $currentUser ? auth_photo_url($currentUser) : $logoUrl;
$systemName = app_visible_name();

$navItems = [
    [
        'label' => 'Portal interno',
        'url' => base_url('/'),
        'path' => base_url('/'),
        'attributes' => '',
    ],
    [
        'label' => 'Directorio',
        'url' => base_url('directorio'),
        'path' => base_url('directorio'),
        'attributes' => '',
    ],
    [
        'label' => 'Solicitudes',
        'url' => base_url('solicitudes'),
        'path' => base_url('solicitudes'),
        'attributes' => '',
    ],
    [
        'label' => 'Documentos',
        'url' => base_url('documentos'),
        'path' => base_url('documentos'),
        'attributes' => '',
    ],
    [
        'label' => 'Newsletter',
        'url' => '#',
        'path' => '#',
        'attributes' => 'data-newsletter-open',
    ],
    [
        'label' => 'Soporte interno',
        'url' => base_url('soporte'),
        'path' => base_url('soporte'),
        'attributes' => '',
    ],
];

if (!function_exists('is_nav_active')) {
    function is_nav_active(string $currentPath, string $itemPath): bool
    {
        if ($itemPath === '#') {
            return false;
        }

        $current = rtrim($currentPath, '/');
        $target = rtrim($itemPath, '/');
        $home = rtrim(base_url('/'), '/');

        if ($target === $home) {
            return $current === $home || $current === rtrim(base_url(''), '/');
        }

        return str_starts_with($current, $target);
    }
}

?>

<div class="app">
    <header class="topbar">
        <div class="wrap topbar__inner">
            <a class="brand" href="<?= e(base_url('/')) ?>" aria-label="<?= e($systemName) ?>">
                <img
                    class="brand__img"
                    src="<?= e($logoUrl) ?>"
                    alt="WOLK-IT">

                <span class="brand__text">
                    <strong class="brand__name">WOLK-IT</strong>
                    <span class="brand__sub">Portal interno</span>
                    <span class="brand__tag">Sistema interno de acceso</span>
                </span>
            </a>

            <button
                class="mobile-menu-btn"
                type="button"
                data-mobile-menu
                data-mobile-menu-open
                aria-expanded="false"
                aria-label="Abrir menú lateral">
                ☰ Menú
            </button>

            <div class="nav-shell" aria-label="Controles de navegación">
                <button class="nav-arrow nav-arrow--left" type="button" data-nav-prev aria-label="Anterior">
                    ‹
                </button>

                <nav class="nav" id="topNav" aria-label="Navegación principal" data-nav-scroll>
                    <?php foreach ($navItems as $item): ?>
                        <a
                            class="nav__link <?= is_nav_active($currentPath, (string) $item['path']) ? 'is-active' : '' ?>"
                            href="<?= e((string) $item['url']) ?>"
                            <?= $item['attributes'] ?>>
                            <?= e((string) $item['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <button class="nav-arrow nav-arrow--right" type="button" data-nav-next aria-label="Siguiente">
                    ›
                </button>
            </div>

            <?php if ($currentUser): ?>
                <details class="user-menu">
                    <summary class="user-access">
                        <span class="user-access__avatar">
                            <img src="<?= e($profilePhotoUrl) ?>" alt="Perfil">
                        </span>

                        <span class="user-access__text">
                            <?= e((string) $currentUser['full_name']) ?>
                        </span>

                        <span class="user-access__chevron">⌄</span>
                    </summary>

                    <div class="user-menu__panel">
                        <div class="user-menu__head">
                            <span class="user-menu__avatar">
                                <img src="<?= e($profilePhotoUrl) ?>" alt="Perfil">
                            </span>

                            <div>
                                <strong><?= e((string) $currentUser['full_name']) ?></strong>
                                <span><?= e((string) $currentUser['position_name']) ?></span>
                            </div>
                        </div>

                        <div class="user-menu__meta">
                            <span><?= e((string) $currentUser['area_name']) ?></span>
                            <span><?= e((string) $currentUser['department_name']) ?></span>

                            <?php if ($currentRoles): ?>
                                <span><?= e(strtoupper(implode(' · ', $currentRoles))) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="user-menu__links">
                            <a href="<?= e(base_url('profile')) ?>">
                                Mi perfil
                            </a>

                            <a href="<?= e(base_url('profile/password')) ?>">
                                Cambiar contraseña
                            </a>

                            <a href="<?= e(base_url('auth/logout.php')) ?>">
                                Cerrar sesión
                            </a>
                        </div>
                    </div>
                </details>
            <?php else: ?>
                <a class="user-access" href="<?= e(base_url('/')) ?>" aria-label="Iniciar sesión">
                    <span class="user-access__avatar">
                        <img src="<?= e($logoUrl) ?>" alt="Perfil">
                    </span>

                    <span class="user-access__text">
                        Iniciar sesión
                    </span>
                </a>
            <?php endif; ?>
        </div>
    </header>