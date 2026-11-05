<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$appName = (string) (app_config()['app']['name'] ?? 'คลังนิยาย');
$year = (int) date('Y') + 543;
$flash = pull_flash();
$flashPayload = null;
if ($flash !== null) {
    $flashPayload = [
        'icon' => $flash['type'],
        'title' => flash_title($flash['type']),
        'html' => nl2br(e($flash['message']), false),
    ];
}
?>
<footer class="site-footer">
    <div class="container-xxl">
        <div class="footer-grid">
            <div>
                <strong><?= e($appName) ?></strong>
                <p>ระบบจัดเก็บนิยายออนไลน์ อ่านต่อได้จากมือถือ แท็บเล็ต และคอมพิวเตอร์</p>
            </div>
            <div>
                <strong>สำรวจ</strong>
                <a href="<?= e(url('novels')) ?>">นิยายทั้งหมด</a>
                <a href="<?= e(url('search')) ?>">ค้นหา</a>
                <a href="<?= e(url('register')) ?>">สมัครสมาชิก</a>
            </div>
            <div>
                <strong>โครงสร้างหน้า</strong>
                <p class="mb-0">header, navbar, sidebar, content และ footer แยกส่วนกัน เพื่อให้ทุกขนาดหน้าจอใช้โครงเดียวกัน</p>
            </div>
        </div>
        <p class="footer-copy">© <?= e((string) $year) ?> <?= e($appName) ?></p>
    </div>
</footer>
<?php if ($flashPayload !== null): ?>
    <script type="application/json" id="flash-data"><?= json_encode($flashPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
<?php
clear_old();
