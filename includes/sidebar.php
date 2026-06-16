<?php

declare(strict_types=1);

$currentPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

if (!function_exists('sidebar_active')) {
    function sidebar_active(array $paths): bool
    {
        global $currentPath;

        foreach ($paths as $path) {
            if ($path === '/') {
                $normalized = rtrim($currentPath, '/');

                if ($normalized === '/nexus' || $normalized === '/nexus/index.php') {
                    return true;
                }

                continue;
            }

            if ($path !== '' && str_contains($currentPath, $path)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('sidebar_icon')) {
    function sidebar_icon(string $name): string
    {
        $icons = [
            'portal' => '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="7" height="7" rx="2"></rect><rect x="13" y="4" width="7" height="7" rx="2"></rect><rect x="4" y="13" width="7" height="7" rx="2"></rect><rect x="13" y="13" width="7" height="7" rx="2"></rect></svg>',
            'directory' => '<svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"></circle><path d="M3 21a6 6 0 0 1 12 0"></path><path d="M17 10h4"></path><path d="M19 8v4"></path></svg>',
            'requests' => '<svg viewBox="0 0 24 24"><path d="M8 4h8"></path><path d="M9 2h6l1 2h3v18H5V4h3l1-2Z"></path><path d="M8 10h8"></path><path d="M8 14h8"></path><path d="M8 18h5"></path></svg>',
            'documents' => '<svg viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6Z"></path><path d="M14 3v5h4"></path><path d="M9 12h6"></path><path d="M9 16h6"></path></svg>',
            'evaluations' => '<svg viewBox="0 0 24 24"><path d="M4 19V5"></path><path d="M4 19h16"></path><path d="M8 16v-5"></path><path d="M12 16V8"></path><path d="M16 16v-7"></path></svg>',
            'resources' => '<svg viewBox="0 0 24 24"><path d="M4 6h7v7H4Z"></path><path d="M13 6h7v7h-7Z"></path><path d="M4 15h7v3H4Z"></path><path d="M13 15h7v3h-7Z"></path></svg>',
            'account' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>',
            'admin' => '<svg viewBox="0 0 24 24"><path d="M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6Z"></path><path d="m9 12 2 2 4-5"></path></svg>',
        ];

        return $icons[$name] ?? $icons['portal'];
    }
}

if (!function_exists('sidebar_link')) {
    function sidebar_link(string $label, string $url, string $icon, array $activePaths): void
    {
        $active = sidebar_active($activePaths) ? ' is-active' : '';

?>
        <a class="sb-link<?= e($active) ?>" href="<?= e($url) ?>">
            <span class="sb-ico" aria-hidden="true">
                <?= sidebar_icon($icon) ?>
            </span>
            <span><?= e($label) ?></span>
        </a>
    <?php
    }
}

if (!function_exists('sidebar_group')) {
    function sidebar_group(string $label, string $icon, array $activePaths, array $items): void
    {
        $open = sidebar_active($activePaths) ? ' open' : '';

    ?>
        <details class="sb-group" <?= $open ?>>
            <summary>
                <span class="sb-ico" aria-hidden="true">
                    <?= sidebar_icon($icon) ?>
                </span>
                <span><?= e($label) ?></span>
            </summary>

            <?php foreach ($items as $item): ?>
                <a class="sb-sub<?= sidebar_active($item['active']) ? ' is-active' : '' ?>" href="<?= e($item['url']) ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </details>
<?php
    }
}

$showAdmin = true;

if (function_exists('auth_can_manage_users') || function_exists('auth_can_view_settings') || function_exists('auth_can_view_access')) {
    $showAdmin = false;

    if (function_exists('auth_can_manage_users') && auth_can_manage_users()) {
        $showAdmin = true;
    }

    if (function_exists('auth_can_view_settings') && auth_can_view_settings()) {
        $showAdmin = true;
    }

    if (function_exists('auth_can_view_access') && auth_can_view_access()) {
        $showAdmin = true;
    }
}

?>

<div class="sidebar-backdrop"></div>

<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar__head">
            <div class="sidebar__title">Portal interno</div>

            <button class="sidebar__toggle" type="button" aria-label="Contraer menú">
                «
            </button>
        </div>

        <?php sidebar_link('Portal interno', base_url('/'), 'portal', ['/']); ?>

        <?php sidebar_link('Directorio', base_url('directorio'), 'directory', ['/directorio', '/directory']); ?>

        <?php
        sidebar_group('Solicitudes', 'requests', ['/solicitudes', '/requests'], [
            [
                'label' => 'Solicitudes generales',
                'url' => base_url('solicitudes'),
                'active' => ['/solicitudes'],
            ],
            [
                'label' => 'Vacaciones y permisos',
                'url' => base_url('solicitudes/vacaciones-permisos.php'),
                'active' => ['/vacaciones', '/permisos'],
            ],
            [
                'label' => 'Solicitud de personal',
                'url' => base_url('solicitudes/personal.php'),
                'active' => ['/personal'],
            ],
            [
                'label' => 'Equipo y formación',
                'url' => base_url('solicitudes/equipo-formacion.php'),
                'active' => ['/equipo-formacion'],
            ],
            [
                'label' => 'Devolución de equipo',
                'url' => base_url('solicitudes/devolucion-equipo.php'),
                'active' => ['/devolucion-equipo'],
            ],
        ]);
        ?>

        <?php
        sidebar_group('Documentos', 'documents', ['/documentos', '/documents', '/formatos', '/manuales', '/politicas'], [
            [
                'label' => 'Documentos internos',
                'url' => base_url('documentos'),
                'active' => ['/documentos', '/documents'],
            ],
            [
                'label' => 'Formatos',
                'url' => base_url('documentos/formatos.php'),
                'active' => ['/formatos'],
            ],
            [
                'label' => 'Manuales',
                'url' => base_url('documentos/manuales.php'),
                'active' => ['/manuales'],
            ],
            [
                'label' => 'Políticas',
                'url' => base_url('documentos/politicas.php'),
                'active' => ['/politicas'],
            ],
        ]);
        ?>

        <?php
        sidebar_group('Evaluaciones', 'evaluations', ['/evaluaciones'], [
            [
                'label' => 'Evaluaciones internas',
                'url' => base_url('evaluaciones'),
                'active' => ['/evaluaciones'],
            ],
        ]);
        ?>

        <?php
        sidebar_group('Recursos', 'resources', ['/recursos', '/soporte', '/newsletter'], [
            [
                'label' => 'Acerca de Wolk IT',
                'url' => base_url('recursos/acerca-de-wolk.php'),
                'active' => ['/acerca'],
            ],
            [
                'label' => 'Newsletter',
                'url' => base_url('recursos/newsletter.php'),
                'active' => ['/newsletter'],
            ],
            [
                'label' => 'Soporte interno',
                'url' => base_url('soporte'),
                'active' => ['/soporte'],
            ],
        ]);
        ?>

        <?php
        sidebar_group('Cuenta', 'account', ['/profile', '/perfil', '/cuenta'], [
            [
                'label' => 'Mi perfil',
                'url' => base_url('profile'),
                'active' => ['/profile'],
            ],
            [
                'label' => 'Cambiar contraseña',
                'url' => base_url('profile/change-password.php'),
                'active' => ['/change-password'],
            ],
        ]);
        ?>

        <?php if ($showAdmin): ?>
            <?php
            sidebar_group('Administración', 'admin', ['/admin'], [
                [
                    'label' => 'Panel administrativo',
                    'url' => base_url('admin'),
                    'active' => ['/admin'],
                ],
                [
                    'label' => 'Usuarios',
                    'url' => base_url('admin/users'),
                    'active' => ['/admin/users'],
                ],
                [
                    'label' => 'Roles y accesos',
                    'url' => base_url('admin/access'),
                    'active' => ['/admin/access', '/admin/roles'],
                ],
            ]);
            ?>
        <?php endif; ?>
    </aside>

    <main class="main">
        <div class="page">