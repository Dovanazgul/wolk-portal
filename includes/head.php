<?php

declare(strict_types=1);

$systemName = function_exists('app_visible_name')
    ? app_visible_name()
    : app_config('full_name', 'Wolk-It Portal interno');

$pageTitle = $pageTitle ?? $systemName;
$pageDescription = $pageDescription ?? app_config('subtitle', 'Sistema interno de accesos, solicitudes, documentos y recursos de Wolk IT');

$pageTitle = str_replace(
    ['Wolk Nexus', 'Nexus'],
    [$systemName, $systemName],
    (string) $pageTitle
);

$pageDescription = str_replace(
    ['Wolk Nexus', 'Nexus'],
    [$systemName, $systemName],
    (string) $pageDescription
);

$assetVersion = time();
$logoPath = asset_url('img/wolk_it_services_logo.jpeg') . '?v=' . $assetVersion;
$cssPath = asset_url('css/style.css') . '?v=' . $assetVersion;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">

    <link rel="icon" type="image/jpeg" href="<?= e($logoPath) ?>">
    <link rel="shortcut icon" type="image/jpeg" href="<?= e($logoPath) ?>">
    <link rel="apple-touch-icon" href="<?= e($logoPath) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Roboto:wght@400;500;700;900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="<?= e($cssPath) ?>">
</head>

<body>