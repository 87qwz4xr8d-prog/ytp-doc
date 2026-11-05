<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$me = current_user();
$displayName = input('display_name');
$email = input('email');
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['password_confirm'] ?? '');
remember_old(['display_name' => $displayName, 'email' => $email]);

$errors = [];
if ($displayName === '' || mb_strlen($displayName) > 100) {
    $errors[] = 'ชื่อที่แสดงต้องมีไม่เกิน 100 ตัวอักษร';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
    $errors[] = 'อีเมลไม่ถูกต้อง';
}

$stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([(int) $me['id']]);
$hash = (string) $stmt->fetchColumn();
if (!password_verify($currentPassword, $hash)) {
    $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
}
if ($newPassword !== '' && mb_strlen($newPassword) < 8) {
    $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
}
if ($newPassword !== '' && !hash_equals($newPassword, $confirm)) {
    $errors[] = 'รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน';
}

$dup = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
$dup->execute([$email, (int) $me['id']]);
if ($dup->fetch()) {
    $errors[] = 'อีเมลนี้ถูกใช้แล้ว';
}

if ($errors !== []) {
    flash('error', implode("\n", $errors));
    redirect(url('profile'));
}

if ($newPassword !== '') {
    $update = db()->prepare('UPDATE users SET display_name = ?, email = ?, password_hash = ? WHERE id = ?');
    $update->execute([$displayName, $email, password_hash($newPassword, PASSWORD_DEFAULT), (int) $me['id']]);
} else {
    $update = db()->prepare('UPDATE users SET display_name = ?, email = ? WHERE id = ?');
    $update->execute([$displayName, $email, (int) $me['id']]);
}

unset($_SESSION['old']);
flash('success', 'บันทึกโปรไฟล์แล้ว');
redirect(url('profile'));
