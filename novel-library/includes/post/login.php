<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$username = input('username');
$password = (string) ($_POST['password'] ?? '');
remember_old(['username' => $username]);

if ($username === '' || $password === '') {
    flash('error', 'กรอกชื่อผู้ใช้และรหัสผ่าน');
    redirect(url('login'));
}

$stmt = db()->prepare('SELECT id, username, display_name, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    flash('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
    redirect(url('login'));
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$target = $_SESSION['redirect_after_login'] ?? null;
unset($_SESSION['redirect_after_login'], $_SESSION['old']);
$target = is_string($target) && is_internal_app_url($target) ? $target : url('home');
flash('success', 'ยินดีต้อนรับ ' . $user['display_name']);
redirect($target);
