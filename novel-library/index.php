<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$routes = require __DIR__ . '/includes/routes.php';
$requested = $_GET['page'] ?? 'home';
$page = is_string($requested) && isset($routes[$requested]) ? $requested : '404';
if ($page === '404') {
    http_response_code(404);
}

$route = $routes[$page];

try {
    db()->query('SELECT 1');
} catch (Throwable $exception) {
    $pageTitle = 'ตั้งค่าฐานข้อมูล';
    $pageDescription = 'ยังเชื่อมต่อฐานข้อมูลไม่ได้';
    $layout = 'auth';
    $dbError = $exception->getMessage();
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/content/setup.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$auth = $route['auth'] ?? null;
if ($auth === 'user') {
    require_login();
} elseif ($auth === 'admin') {
    require_admin();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (empty($route['post']) || !is_string($route['post'])) {
        flash('error', 'หน้านี้ไม่รับการส่งข้อมูล');
        redirect(url('home'));
    }
    verify_csrf();
    $postFile = APP_ROOT . '/includes/post/' . basename($route['post']);
    if (!is_file($postFile)) {
        flash('error', 'ไม่พบตัวจัดการคำขอ');
        redirect(url('home'));
    }
    require $postFile;
    redirect(url((string) ($route['redirect'] ?? 'home')));
}

if (empty($route['file']) || !is_string($route['file'])) {
    redirect(url((string) ($route['redirect'] ?? 'home')));
}

$contentFile = APP_ROOT . '/content/' . $route['file'];
$contentRoot = realpath(APP_ROOT . '/content');
$contentReal = realpath($contentFile);
if ($contentRoot === false || $contentReal === false || !str_starts_with($contentReal, $contentRoot . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    $page = '404';
    $route = $routes['404'];
    $contentReal = realpath(APP_ROOT . '/content/404.php');
}

$pageTitle = (string) $route['title'];
$pageDescription = (string) (app_config()['app']['tagline'] ?? '');
$layout = (string) ($route['layout'] ?? 'app');

ob_start();
require $contentReal;
$mainHtml = ob_get_clean();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
render_flash_fallback();

if ($layout === 'app') {
    echo '<div class="container-xxl page-shell"><div class="row g-4">';
    echo '<div class="col-lg-3 d-none d-lg-block">';
    $sidebarMode = 'desktop';
    require __DIR__ . '/includes/sidebar.php';
    echo '</div><div class="col-lg-9"><main id="content">';
    echo $mainHtml;
    echo '</main></div></div></div>';
    $sidebarMode = 'offcanvas';
    require __DIR__ . '/includes/sidebar.php';
} else {
    echo '<main id="content" class="page-shell layout-main">';
    echo $mainHtml;
    echo '</main>';
}

require __DIR__ . '/includes/footer.php';
