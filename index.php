<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

$systemName = app_visible_name();

if (!auth_check()) {
    $pageTitle = 'Portal del empleado | ' . $systemName;
    $pageDescription = 'Acceso interno al Portal del empleado de Wolk IT.';
    $error = '';

    if (is_post()) {
        verify_csrf();

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Ingresa tu correo y contraseña.';
        } else {
            $statement = db()->prepare("
                SELECT
                    id,
                    full_name,
                    email,
                    password_hash,
                    status
                FROM users
                WHERE email = :email
                LIMIT 1
            ");

            $statement->execute([
                ':email' => $email,
            ]);

            $user = $statement->fetch();

            if (!$user || $user['status'] !== 'activo' || empty($user['password_hash'])) {
                auth_log_event('login_failed', null, $email);
                $error = 'El usuario no existe o no tiene acceso activo.';
            } elseif (!password_verify($password, (string) $user['password_hash'])) {
                auth_log_event('login_failed', (int) $user['id'], (string) $user['email']);
                $error = 'Correo o contraseña incorrectos.';
            } else {
                $updateLogin = db()->prepare("
                    UPDATE users
                    SET last_login_at = NOW()
                    WHERE id = :id
                ");

                $updateLogin->execute([
                    ':id' => $user['id'],
                ]);

                login_user($user);
                auth_redirect_after_login();
            }
        }
    }

    require_once __DIR__ . '/includes/head.php';

?>

    <div class="app">
        <header class="topbar">
            <div class="wrap topbar__inner">
                <a class="brand" href="<?= e(base_url('/')) ?>">
                    <img
                        class="brand__img"
                        src="<?= e(asset_url('img/wolk_it_services_logo.jpeg')) ?>"
                        alt="WOLK-IT">

                    <span class="brand__text">
                        <strong class="brand__name">WOLK-IT</strong>
                        <span class="brand__sub">Portal del empleado</span>
                        <span class="brand__tag">Acceso interno</span>
                    </span>
                </a>

                <a
                    class="login-link"
                    href="https://www.wolk-it.com/"
                    target="_blank"
                    rel="noopener">
                    Sitio corporativo
                </a>
            </div>
        </header>

        <main class="auth-page auth-page--employee">
            <section class="auth-card auth-card--center">
                <div class="brand auth-card__brand">
                    <img
                        class="brand__img"
                        src="<?= e(asset_url('img/wolk_it_services_logo.jpeg')) ?>"
                        alt="WOLK-IT">

                    <span class="brand__text">
                        <strong class="brand__name">WOLK-IT</strong>
                        <span class="brand__sub">Portal del empleado</span>
                        <span class="brand__tag">Acceso interno</span>
                    </span>
                </div>

                <h1>Iniciar sesión</h1>

                <p>
                    Ingresa con tu cuenta autorizada para acceder al Portal del empleado.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert--danger">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input
                            class="form-control"
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($_POST['email'] ?? '') ?>"
                            placeholder="correo@wolk-it.com"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input
                            class="form-control"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            required>
                    </div>

                    <button class="btn btn--primary reset-submit" type="submit">
                        Entrar al Portal del empleado
                    </button>
                </form>

                <div class="hero-actions">
                    <a class="btn btn--ghost" href="<?= e(base_url('auth/forgot-password.php')) ?>">
                        Olvidé mi contraseña
                    </a>
                </div>
            </section>
        </main>

        <?php require __DIR__ . '/includes/footer-content.php'; ?>
    </div>
    </body>

    </html>
<?php

    exit;
}

require_password_updated();
require_profile_confirmed();

$currentUser = auth_user();
$currentRoles = $currentUser ? auth_roles((int) $currentUser['id']) : [];

$isSuperAdmin = false;

if (function_exists('auth_is_superadmin') && auth_is_superadmin()) {
    $isSuperAdmin = true;
}

foreach ($currentRoles as $role) {
    if (in_array(strtoupper((string) $role), ['SUPERADMIN', 'CISO', 'CTO'], true)) {
        $isSuperAdmin = true;
        break;
    }
}

$pageTitle = $isSuperAdmin
    ? 'Centro de mando | ' . $systemName
    : 'Portal del empleado | ' . $systemName;

$pageDescription = $isSuperAdmin
    ? 'Vista interna para seguimiento de operación, seguridad y controles principales.'
    : 'Acceso interno para consultar recursos, solicitudes, documentos y herramientas de Wolk IT.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

?>

<?php if ($isSuperAdmin): ?>
    <div class="super-dashboard">
        <section class="super-hero">
            <div class="super-hero__content">
                <span class="super-hero__label">
                    Centro de mando
                </span>

                <h1>Vista de operación</h1>

                <p>
                    Un resumen para revisar el estado del entorno, ubicar pendientes y entrar rápido a los puntos que necesitan seguimiento.
                </p>

                <div class="super-kpis">
                    <article class="super-kpi">
                        <span class="super-kpi__value">94%</span>
                        <span class="super-kpi__label">Equipos con protección confirmada</span>
                        <span class="super-kpi__status super-kpi__status--ok">Estable</span>
                    </article>

                    <article class="super-kpi">
                        <span class="super-kpi__value">12</span>
                        <span class="super-kpi__label">Cambios que siguen en curso</span>
                        <span class="super-kpi__status super-kpi__status--info">Proceso</span>
                    </article>

                    <article class="super-kpi">
                        <span class="super-kpi__value">3</span>
                        <span class="super-kpi__label">Temas que falta cerrar</span>
                        <span class="super-kpi__status super-kpi__status--warning">Revisar</span>
                    </article>

                    <article class="super-kpi">
                        <span class="super-kpi__value">88%</span>
                        <span class="super-kpi__label">Equipos con cifrado confirmado</span>
                        <span class="super-kpi__status super-kpi__status--ok">Validado</span>
                    </article>
                </div>

                <div class="hero-actions">
                    <a class="btn btn--primary" href="<?= e(base_url('admin/access')) ?>">
                        Revisar accesos
                    </a>

                    <a class="btn btn--ghost" href="<?= e(base_url('admin/security')) ?>">
                        Ver seguridad
                    </a>

                    <a class="btn btn--ghost" href="<?= e(base_url('admin/newsletter')) ?>">
                        Comunicados
                    </a>
                </div>
            </div>

            <div class="super-hero__visual">
                <a class="super-ring-card" href="<?= e(base_url('admin/security')) ?>" aria-label="Abrir vista de seguridad">
                    <svg class="super-ring" viewBox="0 0 260 260" role="img" aria-label="Gráfica general de postura operativa">
                        <circle cx="130" cy="130" r="92" fill="none" stroke="#dbe5ec" stroke-width="22"></circle>
                        <circle cx="130" cy="130" r="92" fill="none" stroke="#0ea5a8" stroke-width="22" stroke-dasharray="426 578" stroke-linecap="round" transform="rotate(-90 130 130)"></circle>
                        <circle cx="130" cy="130" r="64" fill="none" stroke="#e5edf3" stroke-width="16"></circle>
                        <circle cx="130" cy="130" r="64" fill="none" stroke="#37b24d" stroke-width="16" stroke-dasharray="332 402" stroke-linecap="round" transform="rotate(-90 130 130)"></circle>
                        <text x="130" y="122" text-anchor="middle" font-size="38" font-weight="900" fill="#0f172a">87%</text>
                        <text x="130" y="150" text-anchor="middle" font-size="13" font-weight="800" fill="#607084">Estado general</text>
                    </svg>
                </a>
            </div>
        </section>

        <section class="super-grid">
            <article class="super-panel">
                <div class="super-panel__head">
                    <div>
                        <h2>Lectura</h2>
                        <p>
                            Una vista sencilla para saber dónde conviene entrar primero.
                        </p>
                    </div>

                    <span class="pill">Resumen</span>
                </div>

                <svg class="super-chart" viewBox="0 0 620 300" role="img" aria-label="Gráfica de lectura rápida">
                    <line x1="70" y1="238" x2="570" y2="238" stroke="#dbe5ec" stroke-width="2"></line>
                    <line x1="70" y1="188" x2="570" y2="188" stroke="#eef3f7" stroke-width="2"></line>
                    <line x1="70" y1="138" x2="570" y2="138" stroke="#eef3f7" stroke-width="2"></line>
                    <line x1="70" y1="88" x2="570" y2="88" stroke="#eef3f7" stroke-width="2"></line>

                    <a href="<?= e(base_url('admin/access')) ?>" aria-label="Abrir accesos">
                        <rect x="96" y="106" width="54" height="132" rx="12" fill="#0ea5a8"></rect>
                    </a>

                    <a href="<?= e(base_url('admin/changes')) ?>" aria-label="Abrir cambios">
                        <rect x="190" y="148" width="54" height="90" rx="12" fill="#37b24d"></rect>
                    </a>

                    <a href="<?= e(base_url('admin/incidents')) ?>" aria-label="Abrir temas abiertos">
                        <rect x="284" y="78" width="54" height="160" rx="12" fill="#1d4ed8"></rect>
                    </a>

                    <a href="<?= e(base_url('admin/security/pentesting')) ?>" aria-label="Abrir revisión técnica">
                        <rect x="378" y="168" width="54" height="70" rx="12" fill="#f59e0b"></rect>
                    </a>

                    <a href="<?= e(base_url('admin/security/firewall-monitoring')) ?>" aria-label="Abrir red">
                        <rect x="472" y="122" width="54" height="116" rx="12" fill="#16a34a"></rect>
                    </a>

                    <text x="123" y="266" text-anchor="middle" font-size="13" fill="#607084" font-weight="800">Accesos</text>
                    <text x="217" y="266" text-anchor="middle" font-size="13" fill="#607084" font-weight="800">Cambios</text>
                    <text x="311" y="266" text-anchor="middle" font-size="13" fill="#607084" font-weight="800">Temas</text>
                    <text x="405" y="266" text-anchor="middle" font-size="13" fill="#607084" font-weight="800">Revisión</text>
                    <text x="499" y="266" text-anchor="middle" font-size="13" fill="#607084" font-weight="800">Red</text>

                    <text x="96" y="44" font-size="15" fill="#0f172a" font-weight="900">Puntos con movimiento</text>
                    <text x="96" y="64" font-size="12" fill="#607084" font-weight="700">Cada indicador abre su detalle</text>
                </svg>
            </article>

            <article class="super-panel">
                <div class="super-panel__head">
                    <div>
                        <h2>Movimiento semanal</h2>
                        <p>
                            Una lectura del comportamiento reciente de temas abiertos y cambios.
                        </p>
                    </div>

                    <span class="pill">Tendencia</span>
                </div>

                <svg class="super-chart" viewBox="0 0 620 300" role="img" aria-label="Gráfica de movimiento semanal">
                    <line x1="64" y1="232" x2="560" y2="232" stroke="#dbe5ec" stroke-width="2"></line>
                    <line x1="64" y1="174" x2="560" y2="174" stroke="#eef3f7" stroke-width="2"></line>
                    <line x1="64" y1="116" x2="560" y2="116" stroke="#eef3f7" stroke-width="2"></line>
                    <line x1="64" y1="58" x2="560" y2="58" stroke="#eef3f7" stroke-width="2"></line>

                    <a href="<?= e(base_url('admin/incidents')) ?>" aria-label="Abrir temas abiertos">
                        <polyline points="72,210 150,172 228,190 306,122 384,144 462,82 540,104" fill="none" stroke="#0ea5a8" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <circle cx="306" cy="122" r="8" fill="#0ea5a8"></circle>
                        <circle cx="462" cy="82" r="8" fill="#0ea5a8"></circle>
                    </a>

                    <a href="<?= e(base_url('admin/changes')) ?>" aria-label="Abrir cambios">
                        <polyline points="72,222 150,214 228,186 306,198 384,164 462,174 540,138" fill="none" stroke="#37b24d" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <circle cx="540" cy="138" r="8" fill="#37b24d"></circle>
                    </a>

                    <text x="72" y="36" font-size="15" fill="#0f172a" font-weight="900">Actividad reciente</text>
                    <circle cx="74" cy="268" r="6" fill="#0ea5a8"></circle>
                    <text x="90" y="272" font-size="13" fill="#607084" font-weight="800">Temas</text>
                    <circle cx="178" cy="268" r="6" fill="#37b24d"></circle>
                    <text x="194" y="272" font-size="13" fill="#607084" font-weight="800">Cambios</text>
                </svg>
            </article>
        </section>

        <section class="super-grid">
            <article class="super-panel">
                <div class="super-panel__head">
                    <div>
                        <h2>Cobertura técnica</h2>
                        <p>
                            Estado de controles que ayudan a sostener la operación.
                        </p>
                    </div>

                    <span class="pill">Seguridad</span>
                </div>

                <svg class="super-chart" viewBox="0 0 620 300" role="img" aria-label="Gráfica de cobertura técnica">
                    <circle cx="140" cy="148" r="74" fill="none" stroke="#dbe5ec" stroke-width="18"></circle>
                    <a href="<?= e(base_url('admin/security/active-antivirus')) ?>" aria-label="Abrir cobertura">
                        <circle cx="140" cy="148" r="74" fill="none" stroke="#0ea5a8" stroke-width="18" stroke-dasharray="420 465" stroke-linecap="round" transform="rotate(-90 140 148)"></circle>
                    </a>
                    <text x="140" y="142" text-anchor="middle" font-size="28" fill="#0f172a" font-weight="900">94%</text>
                    <text x="140" y="166" text-anchor="middle" font-size="12" fill="#607084" font-weight="800">Protección</text>

                    <circle cx="310" cy="148" r="74" fill="none" stroke="#dbe5ec" stroke-width="18"></circle>
                    <a href="<?= e(base_url('admin/security/encrypted-disk')) ?>" aria-label="Abrir cifrado">
                        <circle cx="310" cy="148" r="74" fill="none" stroke="#37b24d" stroke-width="18" stroke-dasharray="386 465" stroke-linecap="round" transform="rotate(-90 310 148)"></circle>
                    </a>
                    <text x="310" y="142" text-anchor="middle" font-size="28" fill="#0f172a" font-weight="900">88%</text>
                    <text x="310" y="166" text-anchor="middle" font-size="12" fill="#607084" font-weight="800">Cifrado</text>

                    <circle cx="480" cy="148" r="74" fill="none" stroke="#dbe5ec" stroke-width="18"></circle>
                    <a href="<?= e(base_url('admin/security/firewall-monitoring')) ?>" aria-label="Abrir actividad de red">
                        <circle cx="480" cy="148" r="74" fill="none" stroke="#1d4ed8" stroke-width="18" stroke-dasharray="358 465" stroke-linecap="round" transform="rotate(-90 480 148)"></circle>
                    </a>
                    <text x="480" y="142" text-anchor="middle" font-size="28" fill="#0f172a" font-weight="900">77%</text>
                    <text x="480" y="166" text-anchor="middle" font-size="12" fill="#607084" font-weight="800">Red</text>
                </svg>
            </article>

            <article class="super-panel">
                <div class="super-panel__head">
                    <div>
                        <h2>Accesos del equipo</h2>
                        <p>
                            Una revisión rápida de cuentas y perfiles internos.
                        </p>
                    </div>

                    <span class="pill">Accesos</span>
                </div>

                <svg class="super-chart" viewBox="0 0 620 300" role="img" aria-label="Gráfica de accesos del equipo">
                    <a href="<?= e(base_url('admin/access')) ?>" aria-label="Abrir accesos">
                        <rect x="76" y="68" width="430" height="18" rx="9" fill="#e5edf3"></rect>
                        <rect x="76" y="68" width="360" height="18" rx="9" fill="#0ea5a8"></rect>
                    </a>
                    <text x="76" y="50" font-size="14" fill="#0f172a" font-weight="900">Cuentas revisadas</text>
                    <text x="526" y="82" font-size="13" fill="#607084" font-weight="900">84%</text>

                    <a href="<?= e(base_url('admin/access')) ?>" aria-label="Abrir perfiles">
                        <rect x="76" y="132" width="430" height="18" rx="9" fill="#e5edf3"></rect>
                        <rect x="76" y="132" width="302" height="18" rx="9" fill="#37b24d"></rect>
                    </a>
                    <text x="76" y="114" font-size="14" fill="#0f172a" font-weight="900">Perfiles validados</text>
                    <text x="526" y="146" font-size="13" fill="#607084" font-weight="900">70%</text>

                    <a href="<?= e(base_url('admin/access')) ?>" aria-label="Abrir pendientes">
                        <rect x="76" y="196" width="430" height="18" rx="9" fill="#e5edf3"></rect>
                        <rect x="76" y="196" width="118" height="18" rx="9" fill="#f59e0b"></rect>
                    </a>
                    <text x="76" y="178" font-size="14" fill="#0f172a" font-weight="900">Pendientes por confirmar</text>
                    <text x="526" y="210" font-size="13" fill="#607084" font-weight="900">27%</text>
                </svg>
            </article>
        </section>

        <section class="super-panel super-panel--wide">
            <div class="super-panel__head">
                <div>
                    <h2>Espacios de trabajo</h2>
                    <p>
                        Accesos de uso frecuente para revisar pendientes, comunicados y controles de la operación.
                    </p>
                </div>

                <span class="pill">Vista de control</span>
            </div>

            <div class="super-controls">
                <a class="super-control-card" href="<?= e(base_url('admin/access')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M32 8 52 16v14c0 14-8 22-20 26-12-4-20-12-20-26V16Z"></path>
                            <path d="m24 34 6 6 12-16"></path>
                        </svg>
                    </span>

                    <strong>Accesos y permisos</strong>
                    <span>Cuentas y ajustes que conviene mantener revisados.</span>
                    <span class="tag tag--ok">Revisado</span>
                </a>

                <a class="super-control-card" href="<?= e(base_url('admin/incidents')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M32 8 56 52H8Z"></path>
                            <path d="M32 22v14"></path>
                            <path d="M32 44h.1"></path>
                        </svg>
                    </span>

                    <strong>Pendientes abiertos</strong>
                    <span>Reportes y actividades que siguen en curso.</span>
                    <span class="tag tag--warning">Atención</span>
                </a>

                <a class="super-control-card" href="<?= e(base_url('admin/changes')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M18 18h28"></path>
                            <path d="M18 32h20"></path>
                            <path d="M18 46h14"></path>
                            <path d="M42 34l8 8-8 8"></path>
                        </svg>
                    </span>

                    <strong>Cambios internos</strong>
                    <span>Movimientos programados o pendientes de cierre.</span>
                    <span class="tag tag--info">Proceso</span>
                </a>

                <a class="super-control-card" href="<?= e(base_url('admin/newsletter')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M14 16h36v34H14Z"></path>
                            <path d="M22 26h20"></path>
                            <path d="M22 34h20"></path>
                            <path d="M22 42h12"></path>
                        </svg>
                    </span>

                    <strong>Comunicación interna</strong>
                    <span>Publicaciones y mensajes preparados para el equipo.</span>
                    <span class="tag tag--info">Comunicados</span>
                </a>

                <a class="super-control-card" href="<?= e(base_url('admin/security/pentesting')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="22"></circle>
                            <path d="M32 16v32"></path>
                            <path d="M16 32h32"></path>
                            <circle cx="32" cy="32" r="7"></circle>
                        </svg>
                    </span>

                    <strong>Revisión técnica</strong>
                    <span>Pruebas, hallazgos y acciones en seguimiento.</span>
                    <span class="tag tag--warning">Seguimiento</span>
                </a>

                <a class="super-control-card" href="<?= e(base_url('admin/security/antivirus-monitoring')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M32 8 52 16v14c0 14-8 22-20 26-12-4-20-12-20-26V16Z"></path>
                            <path d="m23 32 6 6 13-14"></path>
                        </svg>
                    </span>

                    <strong>Protección de equipos</strong>
                    <span>Estado general de agentes, alertas y equipos pendientes.</span>
                    <span class="tag tag--ok">Protección</span>
                </a>

                <a class="super-control-card" href="<?= e(base_url('admin/security/firewall-monitoring')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M14 16h36v32H14Z"></path>
                            <path d="M14 26h36"></path>
                            <path d="M24 16v10"></path>
                            <path d="M38 16v10"></path>
                            <path d="M24 36h16"></path>
                        </svg>
                    </span>

                    <strong>Actividad de red</strong>
                    <span>Eventos y señales que ayudan a mantener visibilidad del entorno.</span>
                    <span class="tag tag--info">Red</span>
                </a>

                <a class="super-control-card" href="<?= e(base_url('admin/security/encrypted-disk')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <rect x="14" y="24" width="36" height="28" rx="4"></rect>
                            <path d="M22 24v-6a10 10 0 0 1 20 0v6"></path>
                            <circle cx="32" cy="38" r="3"></circle>
                            <path d="M32 41v5"></path>
                        </svg>
                    </span>

                    <strong>Cifrado de equipos</strong>
                    <span>Validación de cifrado activo y equipos pendientes.</span>
                    <span class="tag tag--ok">Validado</span>
                </a>

                <a class="super-control-card" href="<?= e(base_url('admin/security/active-antivirus')) ?>">
                    <span class="super-control-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M12 18h40"></path>
                            <path d="M16 18v30h32V18"></path>
                            <path d="M24 30h16"></path>
                            <path d="M24 40h10"></path>
                            <path d="m42 37 4 4 8-10"></path>
                        </svg>
                    </span>

                    <strong>Cobertura actual</strong>
                    <span>Resumen de equipos cubiertos y puntos por confirmar.</span>
                    <span class="tag tag--ok">Activo</span>
                </a>
            </div>
        </section>
    </div>
<?php else: ?>
    <section class="intro-block">
        <div class="eyebrow">Portal del empleado</div>

        <h1>Portal del empleado</h1>

        <p>
            Consulta tus accesos internos, solicitudes, documentos y recursos disponibles dentro de Wolk IT.
        </p>

        <div class="hero-actions">
            <a class="btn btn--primary" href="<?= e(base_url('solicitudes')) ?>">
                Crear solicitud
            </a>
        </div>
    </section>

    <section class="top-grid">
        <article class="card about-preview">
            <div class="card-head">
                <div>
                    <div class="eyebrow">Acerca de Wolk IT</div>
                    <h2 class="card-title">Conoce la visión de la empresa</h2>
                </div>

                <span class="pill">Uso interno</span>
            </div>

            <p class="card-sub">
                Consulta nuestra misión, visión y valores de Wolk IT.
            </p>

            <div class="hero-actions">
                <button class="btn btn--ghost" type="button" data-about-open>
                    Ver más
                </button>
            </div>
        </article>

        <article class="card newsletter-card">
            <div class="card-head">
                <div>
                    <div class="eyebrow">Newsletter del mes</div>
                    <h2 class="card-title" id="newsletterCurrentTitle">Newsletter del mes</h2>
                </div>

                <span class="pill" id="newsletterCurrentPill">Mes actual</span>
            </div>

            <p class="card-sub">
                Revisa el comunicado interno del mes correspondiente sin salir del sistema.
            </p>

            <div class="newsletter-meta">
                <span class="tag tag--ok">Actualización automática</span>
                <span class="tag tag--info" id="newsletterMonthTag">Mes actual</span>
            </div>

            <div class="hero-actions">
                <button class="btn btn--primary" type="button" data-newsletter-open>
                    Ver newsletter
                </button>
            </div>
        </article>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <div class="eyebrow">Accesos principales</div>
                <h2>Recursos internos</h2>
                <p>
                    Accede a las herramientas y formularios internos de uso frecuente.
                </p>
            </div>
        </div>

        <div class="service-groups">
            <div class="service-board">
                <div class="service-board__head">
                    <h3 class="service-board__title">Gestión interna</h3>
                    <span class="pill">Solicitudes</span>
                </div>

                <div class="service-list">
                    <a class="service-item" href="<?= e(base_url('solicitudes')) ?>">
                        <span class="service-ico" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M18 12h28"></path>
                                <path d="M20 20h24"></path>
                                <path d="M20 28h18"></path>
                                <path d="M20 36h14"></path>
                                <rect x="14" y="8" width="36" height="48" rx="4"></rect>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Solicitudes generales</strong>
                            <span>Canal para registrar requerimientos internos y seguimiento operativo.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--info">Gestión</span>
                            <span class="service-arrow">›</span>
                        </span>
                    </a>

                    <a class="service-item" href="<?= e(base_url('documentos')) ?>">
                        <span class="service-ico service-ico--blue" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M18 10h22l8 8v36H18z"></path>
                                <path d="M40 10v10h8"></path>
                                <path d="M24 30h18"></path>
                                <path d="M24 38h18"></path>
                                <path d="M24 46h10"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Documentos internos</strong>
                            <span>Consulta manuales, formatos, políticas y documentos autorizados.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--ok">Documentos</span>
                            <span class="service-arrow">›</span>
                        </span>
                    </a>

                    <a class="service-item" href="<?= e(base_url('directorio')) ?>">
                        <span class="service-ico service-ico--green" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="20" r="8"></circle>
                                <path d="M18 48c3-13 25-13 28 0"></path>
                                <path d="M48 28h6"></path>
                                <path d="M51 25v6"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Directorio</strong>
                            <span>Consulta información de contacto y referencias internas.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--info">Consulta</span>
                            <span class="service-arrow">›</span>
                        </span>
                    </a>

                    <a class="service-item" href="<?= e(base_url('soporte')) ?>">
                        <span class="service-ico service-ico--blue" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="20"></circle>
                                <path d="M32 18v8"></path>
                                <path d="M32 38v8"></path>
                                <path d="M18 32h8"></path>
                                <path d="M38 32h8"></path>
                            </svg>
                        </span>

                        <span class="service-copy">
                            <strong>Soporte interno</strong>
                            <span>Canal para soporte técnico y seguimiento de incidencias.</span>
                        </span>

                        <span class="service-right">
                            <span class="tag tag--info">Soporte</span>
                            <span class="service-arrow">›</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php

require_once __DIR__ . '/includes/footer.php';
