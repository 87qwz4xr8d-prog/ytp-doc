<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

if (current_user() !== null) {
    redirect(url('home'));
}
?>
<section class="auth-card">
    <h1 class="h3">สมัครสมาชิก</h1>
    <p>บัญชีใหม่เป็นนักอ่าน สามารถเก็บชั้นหนังสือและอ่านต่อจากตอนที่ค้างไว้</p>
    <form method="post" action="<?= e(url('register')) ?>">
        <?= csrf_field() ?>
        <div class="hp" aria-hidden="true">
            <label>เว็บไซต์ <input name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="mb-3">
            <label class="form-label" for="display_name">ชื่อที่แสดง</label>
            <input class="form-control" id="display_name" name="display_name" value="<?= e((string) old('display_name')) ?>" required maxlength="100">
        </div>
        <div class="mb-3">
            <label class="form-label" for="username">ชื่อผู้ใช้</label>
            <input class="form-control" id="username" name="username" value="<?= e((string) old('username')) ?>" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+" autocomplete="username">
            <div class="form-text">ใช้ภาษาอังกฤษ ตัวเลข และขีดล่าง 3–50 ตัว</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">อีเมล</label>
            <input class="form-control" id="email" name="email" type="email" value="<?= e((string) old('email')) ?>" required maxlength="120" autocomplete="email">
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">รหัสผ่าน</label>
            <input class="form-control" id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirm">ยืนยันรหัสผ่าน</label>
            <input class="form-control" id="password_confirm" name="password_confirm" type="password" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn btn-seal w-100" type="submit">สร้างบัญชี</button>
    </form>
    <p class="mt-3 mb-0">มีบัญชีอยู่แล้ว? <a href="<?= e(url('login')) ?>">เข้าสู่ระบบ</a></p>
</section>
