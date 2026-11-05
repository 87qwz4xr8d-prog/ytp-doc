<?php

declare(strict_types=1);

/**
 * เราเตอร์สำหรับเซิร์ฟเวอร์ทดลองของ PHP
 * คำสั่ง: php -S 127.0.0.1:8080 -t novel-library novel-library/router.php
 * ไฟล์จริงเช่น CSS และรูปจะถูกส่งตรง ส่วนเส้นทางของระบบจะเข้า index.php
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';
$blocked = ['/config', '/includes', '/storage', '/sql'];

foreach ($blocked as $prefix) {
    if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        return;
    }
}

if (str_contains($path, '/.')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    return;
}

$full = realpath(__DIR__ . $path);
$root = realpath(__DIR__);
if ($full !== false && $root !== false && str_starts_with($full, $root) && is_file($full)) {
    return false;
}

require __DIR__ . '/index.php';
