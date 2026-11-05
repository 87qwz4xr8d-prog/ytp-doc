<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$me = current_user();
?>
<div class="page-heading">
    <h1>โปรไฟล์</h1>
    <p>สมาชิกตั้งแต่ <?= e(thai_datetime((string) $me['created_at'])) ?> · สิทธิ์ <?= $me['role'] === 'admin' ? 'ผู้ดูแล' : 'นักอ่าน' ?></p>
</div>
<form class="panel p-3 p-md-4" method="post" action="<?= e(url('profile')) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="username">ชื่อผู้ใช้</label>
            <input class="form-control" id="username" value="<?= e($me['username']) ?>" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="display_name">ชื่อที่แสดง</label>
            <input class="form-control" id="display_name" name="display_name" value="<?= e((string) old('display_name', $me['display_name'])) ?>" required maxlength="100">
        </div>
        <div class="col-12">
            <label class="form-label" for="email">อีเมล</label>
            <input class="form-control" id="email" type="email" name="email" value="<?= e((string) old('email', $me['email'])) ?>" required maxlength="120">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="current_password">รหัสผ่านปัจจุบัน</label>
            <input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="password">รหัสผ่านใหม่</label>
            <input class="form-control" id="password" type="password" name="password" autocomplete="new-password" minlength="8">
            <div class="form-text">เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="password_confirm">ยืนยันรหัสผ่านใหม่</label>
            <input class="form-control" id="password_confirm" type="password" name="password_confirm" autocomplete="new-password" minlength="8">
        </div>
    </div>
    <button class="btn btn-seal mt-3" type="submit">บันทึกโปรไฟล์</button>
</form>
