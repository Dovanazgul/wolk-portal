<?php

declare(strict_types=1);

$databaseConfig = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'wolk_nexus',
    'username' => 'root',
    'password' => 'Wolkero*012',
    'charset' => 'utf8mb4',
];

function database_config(string $key, mixed $default = null): mixed
{
    global $databaseConfig;

    return $databaseConfig[$key] ?? $default;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) database_config('host');
    $port = (int) database_config('port');
    $database = (string) database_config('database');
    $charset = (string) database_config('charset');
    $username = (string) database_config('username');
    $password = (string) database_config('password');

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (PDOException $exception) {
        exit('No se pudo conectar con la base de datos del Portal interno.');
    }
}
