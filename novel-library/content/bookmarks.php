<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$me = current_user();
$rows = bookmarks_of((int) $me['id']);
?>
<div class="page-heading">
    <h1>ชั้นหนังสือของฉัน</h1>
    <p>เรื่องที่เก็บไว้จะอยู่กับบัญชีนี้บนทุกอุปกรณ์</p>
</div>
<?php if ($rows === []): ?>
    <?php render_empty('fa-bookmark', 'ยังไม่ได้เก็บเรื่องไว้', 'เปิดหน้านิยายแล้วกดเก็บเข้าชั้นหนังสือ'); ?>
    <p class="text-center"><a class="btn btn-seal" href="<?= e(url('novels')) ?>">ไปเลือกนิยาย</a></p>
<?php else: ?>
    <div class="novel-grid">
        <?php foreach ($rows as $novel): ?>
            <?php include APP_ROOT . '/includes/partials/novel-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
