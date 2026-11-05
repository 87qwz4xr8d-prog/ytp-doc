<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$action = input('action');
$id = input_int('id');
$me = current_user();
$user = find_user($id);

if ($user === null) {
    flash('error', 'ไม่พบบัญชี');
    redirect(url('admin-users'));
}
if ((int) $user['id'] === (int) $me['id']) {
    flash('error', 'เปลี่ยนสิทธิ์หรือลบบัญชีที่กำลังใช้งานอยู่ไม่ได้');
    redirect(url('admin-users'));
}

$adminCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

if ($action === 'delete') {
    if ($user['role'] === 'admin' && $adminCount <= 1) {
        flash('error', 'ต้องมีผู้ดูแลอย่างน้อยหนึ่งบัญชี');
        redirect(url('admin-users'));
    }
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    flash('success', 'ลบบัญชีแล้ว');
    redirect(url('admin-users'));
}

$role = input('role');
if (!in_array($role, ['admin', 'member'], true)) {
    flash('error', 'สิทธิ์ไม่ถูกต้อง');
    redirect(url('admin-users'));
}
if ($user['role'] === 'admin' && $role !== 'admin' && $adminCount <= 1) {
    flash('error', 'ต้องมีผู้ดูแลอย่างน้อยหนึ่งบัญชี');
    redirect(url('admin-users'));
}

db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
flash('success', 'บันทึกสิทธิ์แล้ว');
redirect(url('admin-users'));
