<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

if (input('website') !== '') {
    flash('success', 'หากข้อมูลถูกต้อง ระบบจะสร้างบัญชีให้');
    redirect(url('login'));
}

$displayName = input('display_name');
$username = input('username');
$email = input('email');
$password = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['password_confirm'] ?? '');
remember_old([
    'display_name' => $displayName,
    'username' => $username,
    'email' => $email,
]);

$errors = [];
if ($displayName === '' || mb_strlen($displayName) > 100) {
    $errors[] = 'ชื่อที่แสดงต้องมีไม่เกิน 100 ตัวอักษร';
}
if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    $errors[] = 'ชื่อผู้ใช้ใช้ได้เฉพาะภาษาอังกฤษ ตัวเลข และขีดล่าง ความยาว 3–50 ตัว';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
    $errors[] = 'อีเมลไม่ถูกต้อง';
}
if (mb_strlen($password) < 8) {
    $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
}
if (!hash_equals($password, $confirm)) {
    $errors[] = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
}

if ($errors === []) {
    $stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว';
    }
}

if ($errors !== []) {
    flash('error', implode("\n", $errors));
    redirect(url('register'));
}

$stmt = db()->prepare(
    'INSERT INTO users (username, email, password_hash, display_name, role) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $displayName, 'member']);
session_regenerate_id(true);
$_SESSION['user_id'] = (int) db()->lastInsertId();
unset($_SESSION['old']);
flash('success', 'สมัครสมาชิกแล้ว เริ่มเลือกเรื่องที่อยากอ่านได้เลย');
redirect(url('home'));
