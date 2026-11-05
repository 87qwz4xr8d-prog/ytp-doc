<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$appName = (string) (app_config()['app']['name'] ?? 'คลังนิยาย');
$pageTitle = $pageTitle ?? $appName;
$pageDescription = $pageDescription ?? (string) (app_config()['app']['tagline'] ?? '');
$layout = $layout ?? 'app';
$robots = str_starts_with((string) ($page ?? ''), 'admin') ? 'noindex, nofollow' : 'index, follow';
?>
<!doctype html>
<html lang="th" data-theme="paper">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#2a211c">
    <meta name="robots" content="<?= e($robots) ?>">
    <title><?= e($pageTitle) ?> · <?= e($appName) ?></title>
    <link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@500;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
    <script src="<?= e(asset('js/theme-init.js')) ?>"></script>
</head>
<body class="layout-<?= e($layout) ?>" data-page="<?= e((string) ($page ?? '')) ?>">
<a class="skip-link" href="#content">ข้ามไปยังเนื้อหา</a>
