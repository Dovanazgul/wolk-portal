<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

guest_only();

$systemName = app_visible_name();

$pageTitle = 'Restablecer contraseña | ' . $systemName;
$pageDescription = 'Restablecimiento de contraseña del Portal del empleado de Wolk IT.';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenHash = $token !== '' ? hash('sha256', $token) : '';
$portalUrl = app_url('');

$error = '';
$success = '';
$tokenData = null;

function reset_user_columns(): array
{
    static $columns = null;

    if ($columns !== null) {
        return $columns;
    }

    $statement = db()->query("SHOW COLUMNS FROM users");
    $rows = $statement->fetchAll();

    $columns = [];

    foreach ($rows as $row) {
        if (isset($row['Field'])) {
            $columns[] = (string) $row['Field'];
        }
    }

    return $columns;
}

function reset_password_errors(string $password, string $passwordConfirm): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener mínimo 8 caracteres.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Debe incluir al menos una letra minúscula.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Debe incluir al menos una letra mayúscula.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Debe incluir al menos un número.';
    }

    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Debe incluir al menos un símbolo.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'La confirmación de contraseña no coincide.';
    }

    return $errors;
}

function reset_load_token(string $tokenHash): ?array
{
    if ($tokenHash === '') {
        return null;
    }

    $statement = db()->prepare("
        SELECT
            password_reset_tokens.id AS token_id,
            password_reset_tokens.user_id,
            password_reset_tokens.email,
            password_reset_tokens.expires_at,
            password_reset_tokens.used_at,
            users.full_name,
            users.status,
            CASE
                WHEN password_reset_tokens.expires_at < NOW() THEN 1
                ELSE 0
            END AS is_expired
        FROM password_reset_tokens
        INNER JOIN users ON users.id = password_reset_tokens.user_id
        WHERE password_reset_tokens.token_hash = :token_hash
        LIMIT 1
    ");

    $statement->execute([
        ':token_hash' => $tokenHash,
    ]);

    $result = $statement->fetch();

    return $result ?: null;
}

if ($tokenHash !== '') {
    $tokenData = reset_load_token($tokenHash);
}

if (!$tokenData) {
    $error = 'El enlace no es válido o ya no se encuentra disponible.';
} elseif (!empty($tokenData['used_at'])) {
    $error = 'Este enlace ya fue utilizado.';
} elseif ((int) $tokenData['is_expired'] === 1) {
    $error = 'Este enlace ha expirado. Solicita uno nuevo desde la pantalla de recuperación.';
} elseif ((string) $tokenData['status'] !== 'activo') {
    $error = 'El usuario no tiene acceso activo.';
}

if (is_post() && $tokenData && $error === '') {
    verify_csrf();

    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    $validationErrors = reset_password_errors($password, $passwordConfirm);

    if ($validationErrors !== []) {
        $error = implode(' ', $validationErrors);
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $columns = reset_user_columns();

        $set = [
            'password_hash = :password_hash',
        ];

        $params = [
            ':password_hash' => $passwordHash,
            ':user_id' => $tokenData['user_id'],
        ];

        if (in_array('password_changed_at', $columns, true)) {
            $set[] = 'password_changed_at = NOW()';
        }

        if (in_array('password_updated_at', $columns, true)) {
            $set[] = 'password_updated_at = NOW()';
        }

        if (in_array('must_change_password', $columns, true)) {
            $set[] = 'must_change_password = 0';
        }

        if (in_array('force_password_change', $columns, true)) {
            $set[] = 'force_password_change = 0';
        }

        $updateUser = db()->prepare("
            UPDATE users
            SET " . implode(', ', $set) . "
            WHERE id = :user_id
            LIMIT 1
        ");

        $updateUser->execute($params);

        $updateTokens = db()->prepare("
            UPDATE password_reset_tokens
            SET used_at = NOW()
            WHERE user_id = :user_id
            AND used_at IS NULL
        ");

        $updateTokens->execute([
            ':user_id' => $tokenData['user_id'],
        ]);

        auth_log_event('password_reset_completed', (int) $tokenData['user_id'], (string) $tokenData['email']);

        $success = 'Tu contraseña fue actualizada correctamente. Ya puedes iniciar sesión.';
        $error = '';
    }
}

require_once __DIR__ . '/../includes/head.php';

?>

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
                <span class="brand__tag">Restablecimiento de acceso</span>
            </span>
        </a>

        <a class="login-link" href="<?= e(base_url('/')) ?>">
            Iniciar sesión
        </a>
    </div>
</header>

<main class="auth-page auth-page--employee">
    <section class="reset-page">
        <aside class="reset-hero">
            <div class="reset-hero__brand">
                <img
                    src="<?= e(asset_url('img/wolk_it_services_logo.jpeg')) ?>"
                    alt="WOLK-IT">

                <span>
                    <strong>WOLK-IT</strong>
                    <span>Seguridad de acceso</span>
                </span>
            </div>

            <div class="reset-hero__content">
                <div class="reset-hero__label"></div>

                <h1>Protege tu acceso al Portal del empleado</h1>

                <p>
                    Tu contraseña debe cumplir los lineamientos internos y guardarse de forma segura en KeePass.
                </p>
            </div>

            <div class="reset-hero__steps">
                <div class="reset-step">
                    <span class="reset-step__num">1</span>
                    <span>
                        <strong>Crea una contraseña única</strong>
                        <span>No reutilices contraseñas de otros sistemas o aplicaciones.</span>
                    </span>
                </div>

                <div class="reset-step">
                    <span class="reset-step__num">2</span>
                    <span>
                        <strong>Guarda el acceso en KeePass</strong>
                        <span>Registra el enlace del portal y tus credenciales en el gestor autorizado.</span>
                    </span>
                </div>

                <div class="reset-step">
                    <span class="reset-step__num">3</span>
                    <span>
                        <strong>No compartas credenciales</strong>
                        <span>Las contraseñas son personales, intransferibles y no deben enviarse en texto plano.</span>
                    </span>
                </div>
            </div>
        </aside>

        <section class="reset-card">
            <div class="reset-card__head">
                <div>
                    <span class="reset-badge">Nueva contraseña</span>
                    <h2>Restablecer contraseña</h2>
                    <p>
                        Define una contraseña segura para continuar usando el Portal del empleado.
                    </p>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert--danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert">
                    <?= e($success) ?>
                </div>

                <div class="hero-actions">
                    <a class="btn btn--primary" href="<?= e(base_url('/')) ?>">
                        Ir al inicio de sesión
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($tokenData && $error === '' && $success === ''): ?>
                <form method="POST" autocomplete="off" id="resetPasswordForm">
                    <?= csrf_field() ?>

                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div class="form-group">
                        <label for="password">Nueva contraseña</label>

                        <div class="password-input">
                            <input
                                class="form-control"
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Ingresa tu nueva contraseña"
                                autocomplete="new-password"
                                required>

                            <button
                                class="password-toggle"
                                type="button"
                                data-password-toggle="password"
                                aria-pressed="false">
                                Ver
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirmar contraseña</label>

                        <div class="password-input">
                            <input
                                class="form-control"
                                type="password"
                                id="password_confirm"
                                name="password_confirm"
                                placeholder="Confirma tu nueva contraseña"
                                autocomplete="new-password"
                                required>

                            <button
                                class="password-toggle"
                                type="button"
                                data-password-toggle="password_confirm"
                                aria-pressed="false">
                                Ver
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Seguridad de la contraseña</label>

                        <div class="password-meter" aria-hidden="true">
                            <div class="password-meter__bar" id="passwordStrengthBar"></div>
                        </div>

                        <small class="password-meter__text" id="passwordStrengthText">
                            Ingresa una contraseña para evaluar su seguridad.
                        </small>

                        <div class="password-rules">
                            <span class="password-rule" data-rule="length">8 caracteres</span>
                            <span class="password-rule" data-rule="lower">Minúscula</span>
                            <span class="password-rule" data-rule="upper">Mayúscula</span>
                            <span class="password-rule" data-rule="number">Número</span>
                            <span class="password-rule" data-rule="symbol">Símbolo</span>
                            <span class="password-rule" data-rule="match">Coinciden</span>
                        </div>
                    </div>

                    <div class="reset-help">
                        <span>
                            <strong>Lineamientos internos de contraseña</strong>
                            <span>
                                Basado en el documento 5. Wolk- v3.0 - Política de Seguridad de la Información.
                            </span>
                        </span>

                        <button class="btn btn--ghost" type="button" data-password-policy-open>
                            Ver reglas
                        </button>
                    </div>

                    <button class="btn btn--primary reset-submit" type="submit">
                        Guardar nueva contraseña
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($success === ''): ?>
                <div class="hero-actions">
                    <a class="btn btn--ghost" href="<?= e(base_url('/')) ?>">
                        Volver al inicio de sesión
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>

<div class="policy-modal" id="passwordPolicyModal" aria-hidden="true">
    <div class="policy-modal__backdrop" data-password-policy-close></div>

    <section class="policy-modal__panel" role="dialog" aria-modal="true" aria-labelledby="passwordPolicyTitle">
        <div class="policy-modal__head">
            <div>
                <span class="reset-badge">Política interna</span>
                <h2 id="passwordPolicyTitle">Lineamientos para contraseña</h2>
                <p>
                    Reglas aplicables de acuerdo con el documento
                    <strong>5. Wolk- v3.0 - Política de Seguridad de la Información</strong>.
                </p>
            </div>

            <button class="policy-modal__close" type="button" data-password-policy-close aria-label="Cerrar">
                ×
            </button>
        </div>

        <div class="policy-modal__body">
            <div class="policy-grid">
                <div class="policy-item">
                    <strong>Credenciales personales</strong>
                    <span>Las contraseñas son personales e intransferibles. No deben compartirse ni enviarse en texto plano por medios no seguros.</span>
                </div>

                <div class="policy-item">
                    <strong>Cambio desde el primer uso</strong>
                    <span>Las contraseñas generadas por defecto o automáticamente deben cambiarse desde el primer uso.</span>
                </div>

                <div class="policy-item">
                    <strong>Contraseña única</strong>
                    <span>No se utiliza la misma contraseña para más de un sistema o aplicación.</span>
                </div>

                <div class="policy-item">
                    <strong>Composición mínima</strong>
                    <span>Mínimo 8 caracteres, con minúsculas, mayúsculas, números y símbolos.</span>
                </div>

                <div class="policy-item">
                    <strong>Uso de KeePass</strong>
                    <span>Todos los colaboradores deben utilizar KeePass como gestor de contraseñas y guardar ahí el enlace del Portal del empleado: <?= e($portalUrl) ?></span>
                </div>

                <div class="policy-item">
                    <strong>Segundo factor</strong>
                    <span>Configura 2FA en todos los sistemas y aplicaciones donde exista esta opción.</span>
                </div>

                <div class="policy-item">
                    <strong>Resguardo seguro</strong>
                    <span>No escribas ni guardes contraseñas o PINs junto a computadoras, teléfonos, libretas, notas o lugares visibles.</span>
                </div>

                <div class="policy-item">
                    <strong>Cambio periódico</strong>
                    <span>Cambia tus contraseñas regularmente y cuando detectes actividad sospechosa.</span>
                </div>
            </div>
        </div>

        <div class="policy-modal__actions">
            <button class="btn btn--primary" type="button" data-password-policy-close>
                Entendido
            </button>
        </div>
    </section>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    const resetForm = document.getElementById('resetPasswordForm');
    const policyModal = document.getElementById('passwordPolicyModal');
    const policyOpenButtons = document.querySelectorAll('[data-password-policy-open]');
    const policyCloseButtons = document.querySelectorAll('[data-password-policy-close]');
    const passwordToggleButtons = document.querySelectorAll('[data-password-toggle]');
    const rules = document.querySelectorAll('.password-rule');

    function getPasswordRules(password, confirmation) {
        return {
            length: password.length >= 8,
            lower: /[a-z]/.test(password),
            upper: /[A-Z]/.test(password),
            number: /[0-9]/.test(password),
            symbol: /[^a-zA-Z0-9]/.test(password),
            match: password.length > 0 && password === confirmation
        };
    }

    function updatePasswordStrength() {
        if (!passwordInput || !passwordConfirmInput || !strengthBar || !strengthText) {
            return;
        }

        const password = passwordInput.value;
        const confirmation = passwordConfirmInput.value;
        const result = getPasswordRules(password, confirmation);
        const score = Object.values(result).filter(Boolean).length;

        rules.forEach((rule) => {
            const key = rule.getAttribute('data-rule');

            if (result[key]) {
                rule.classList.add('is-ok');
            } else {
                rule.classList.remove('is-ok');
            }
        });

        strengthBar.className = 'password-meter__bar';

        if (password.length === 0) {
            strengthText.textContent = 'Ingresa una contraseña para evaluar su seguridad.';
            return;
        }

        if (score <= 3) {
            strengthBar.classList.add('is-low');
            strengthText.textContent = 'Seguridad baja: agrega los elementos faltantes.';
            return;
        }

        if (score <= 5) {
            strengthBar.classList.add('is-medium');
            strengthText.textContent = 'Seguridad media: revisa que cumpla todos los requisitos.';
            return;
        }

        strengthBar.classList.add('is-high');
        strengthText.textContent = 'Seguridad alta: cumple con los lineamientos mínimos.';
    }

    function validatePasswordBeforeSubmit(event) {
        const password = passwordInput ? passwordInput.value : '';
        const confirmation = passwordConfirmInput ? passwordConfirmInput.value : '';
        const result = getPasswordRules(password, confirmation);
        const isValid = Object.values(result).every(Boolean);

        if (!isValid) {
            event.preventDefault();
            alert('La contraseña debe tener mínimo 8 caracteres, minúsculas, mayúsculas, números, símbolos y coincidir con la confirmación.');
        }
    }

    function openPolicyModal() {
        if (!policyModal) {
            return;
        }

        policyModal.classList.add('is-open');
        policyModal.setAttribute('aria-hidden', 'false');
    }

    function closePolicyModal() {
        if (!policyModal) {
            return;
        }

        policyModal.classList.remove('is-open');
        policyModal.setAttribute('aria-hidden', 'true');
    }

    function togglePasswordVisibility(button) {
        const inputId = button.getAttribute('data-password-toggle');
        const input = document.getElementById(inputId);

        if (!input) {
            return;
        }

        const isVisible = input.type === 'text';

        input.type = isVisible ? 'password' : 'text';
        button.textContent = isVisible ? 'Ver' : 'Ocultar';
        button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', updatePasswordStrength);
    }

    if (passwordConfirmInput) {
        passwordConfirmInput.addEventListener('input', updatePasswordStrength);
    }

    if (resetForm) {
        resetForm.addEventListener('submit', validatePasswordBeforeSubmit);
    }

    policyOpenButtons.forEach((button) => {
        button.addEventListener('click', openPolicyModal);
    });

    policyCloseButtons.forEach((button) => {
        button.addEventListener('click', closePolicyModal);
    });

    passwordToggleButtons.forEach((button) => {
        button.addEventListener('click', () => togglePasswordVisibility(button));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePolicyModal();
        }
    });
</script>

<?php require __DIR__ . '/../includes/footer-content.php'; ?>
</div>
</body>

</html>