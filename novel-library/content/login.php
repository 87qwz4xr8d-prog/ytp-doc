<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

if (current_user() !== null) {
    redirect(url('home'));
}

$back = (string) ($_GET['back'] ?? '');
if (is_internal_app_url($back)) {
    $_SESSION['redirect_after_login'] = $back;
}
$showDemo = (bool) (app_config()['app']['show_demo_accounts'] ?? false);
?>
<section class="auth-card">
    <h1 class="h3">เข้าสู่ระบบ</h1>
    <p>ใช้ชื่อผู้ใช้หรืออีเมลที่สมัครไว้</p>
    <?php if ($showDemo): ?>
        <div class="demo-box">
            <strong>บัญชีทดลอง</strong>
            <p class="mb-2">ผู้ดูแล <code>admin</code> / <code>Admin@1234</code><br>นักอ่าน <code>reader</code> / <code>Reader@1234</code></p>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-fill-username="admin" data-fill-password="Admin@1234">เติมบัญชีผู้ดูแล</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-fill-username="reader" data-fill-password="Reader@1234">เติมบัญชีนักอ่าน</button>
            </div>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('login')) ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="username">ชื่อผู้ใช้หรืออีเมล</label>
            <input class="form-control" id="username" name="username" value="<?= e((string) old('username')) ?>" autocomplete="username" required maxlength="120">
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">รหัสผ่าน</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-seal w-100" type="submit">เข้าสู่ระบบ</button>
    </form>
    <p class="mt-3 mb-0">ยังไม่มีบัญชี? <a href="<?= e(url('register')) ?>">สมัครสมาชิก</a></p>
</section>
