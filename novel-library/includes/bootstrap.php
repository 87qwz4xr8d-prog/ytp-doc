<?php

declare(strict_types=1);

/**
 * จุดเริ่มต้นของทุกคำขอ
 * ขั้นที่ 1 ตรวจรุ่น PHP
 * ขั้นที่ 2 โหลดค่าตั้ง
 * ขั้นที่ 3 เปิดเซสชัน
 * ขั้นที่ 4 โหลดฟังก์ชันส่วนกลาง
 * การเชื่อมฐานข้อมูลยังไม่เกิดในไฟล์นี้ จะเชื่อมเมื่อมีหน้าเรียกใช้จริง
 */

if (PHP_VERSION_ID < 80200) {
    http_response_code(500);
    exit('ระบบนี้ต้องใช้ PHP 8.2.0 ขึ้นไป เวอร์ชันปัจจุบันคือ ' . PHP_VERSION);
}

define('APP_BOOTSTRAPPED', true);
define('APP_ROOT', dirname(__DIR__));

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $loaded = require APP_ROOT . '/config/config.php';
        $config = is_array($loaded) ? $loaded : [];
    }

    return $config;
}

function app_base_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/' || $dir === '.' || $dir === '') {
        return '';
    }

    return rtrim($dir, '/');
}

$config = app_config();
date_default_timezone_set((string) ($config['app']['timezone'] ?? 'Asia/Bangkok'));

if (!($config['app']['debug'] ?? false)) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$sessionPath = APP_ROOT . '/storage/sessions';
if (!is_dir($sessionPath) && !mkdir($sessionPath, 0775, true) && !is_dir($sessionPath)) {
    http_response_code(500);
    exit('สร้างโฟลเดอร์เซสชันไม่ได้');
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_save_path($sessionPath);
session_name((string) ($config['app']['session_name'] ?? 'klangniyai'));
$cookiePath = app_base_path();
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookiePath === '' ? '/' : $cookiePath,
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

require APP_ROOT . '/includes/functions.php';
require APP_ROOT . '/includes/library.php';

send_security_headers();

set_exception_handler(static function (Throwable $e): void {
    $logDir = APP_ROOT . '/storage';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    $line = sprintf(
        "[%s] %s in %s:%d\n%s\n",
        date('c'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    file_put_contents($logDir . '/error.log', $line, FILE_APPEND);

    if (!headers_sent()) {
        http_response_code(500);
    }

    $debug = (bool) (app_config()['app']['debug'] ?? false);
    echo '<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>เกิดข้อผิดพลาด</title><body style="font-family:Sarabun,sans-serif;background:#f4efe6;color:#231c18;padding:2rem">';
    echo '<h1>ระบบขัดข้องชั่วคราว</h1>';
    echo '<p>ยังเปิดหน้านี้ไม่ได้ ตรวจการเชื่อมต่อฐานข้อมูลและค่าใน config/config.php</p>';
    if ($debug) {
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
    }
    echo '</body></html>';
    exit;
});
