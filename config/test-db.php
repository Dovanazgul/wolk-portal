<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$systemName = app_visible_name();
$pageTitle = 'Prueba de conexión | ' . $systemName;

$databaseName = '';
$tables = [];
$error = '';

try {
    $pdo = db();

    $databaseName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $exception) {
    $error = 'No se pudo conectar con la base de datos.';
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f7fa;
            color: #0f172a;
            font-family: Arial, sans-serif;
            padding: 42px 24px;
        }

        .card {
            width: min(860px, 100%);
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dbe5ec;
            border-radius: 22px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 16px;
            font-size: 32px;
            color: #00513f;
        }

        p {
            line-height: 1.6;
            color: #475569;
        }

        .alert {
            padding: 16px;
            border-radius: 14px;
            margin: 18px 0;
            font-weight: 700;
        }

        .alert--ok {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .alert--error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .meta {
            margin: 22px 0;
            display: grid;
            gap: 10px;
        }

        code {
            display: inline-flex;
            background: #eef2f7;
            border-radius: 8px;
            padding: 4px 8px;
            font-size: 13px;
        }

        ul {
            margin: 18px 0 0;
            padding-left: 24px;
            columns: 2;
        }

        li {
            margin-bottom: 8px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border-radius: 12px;
            background: #0ea5a8;
            color: #ffffff;
            text-decoration: none;
            font-weight: 800;
        }

        .btn--ghost {
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #dbe5ec;
        }

        @media (max-width: 700px) {
            ul {
                columns: 1;
            }
        }
    </style>
</head>

<body>
    <main class="card">
        <h1>Portal interno conectado correctamente</h1>

        <?php if ($error !== ''): ?>
            <div class="alert alert--error">
                <?= e($error) ?>
            </div>
        <?php else: ?>
            <div class="alert alert--ok">
                La conexión con MySQL funciona correctamente.
            </div>

            <div class="meta">
                <p>
                    <strong>Base de datos activa:</strong>
                    <code><?= e($databaseName) ?></code>
                </p>

                <p>
                    <strong>Tablas detectadas:</strong>
                    <?= e((string) count($tables)) ?>
                </p>
            </div>

            <?php if ($tables): ?>
                <ul>
                    <?php foreach ($tables as $table): ?>
                        <li><?= e((string) $table) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

        <div class="actions">
            <a class="btn" href="<?= e(base_url('/')) ?>">
                Volver al inicio
            </a>

            <a class="btn btn--ghost" href="<?= e(base_url('auth/login.php')) ?>">
                Ir al login
            </a>
        </div>
    </main>
</body>

</html>