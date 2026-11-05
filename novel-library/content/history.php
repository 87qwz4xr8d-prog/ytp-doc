<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$me = current_user();
$rows = reading_history((int) $me['id']);
?>
<div class="page-heading">
    <h1>อ่านค้างไว้</h1>
    <p>ระบบจำตอนล่าสุดของแต่ละเรื่องไว้ให้เปิดต่อได้ทันที</p>
</div>
<?php if ($rows === []): ?>
    <?php render_empty('fa-clock-rotate-left', 'ยังไม่มีประวัติการอ่าน', 'เมื่อเข้าสู่ระบบแล้วเปิดอ่าน ตอนล่าสุดจะถูกจำไว้ที่นี่'); ?>
<?php else: ?>
    <div class="continue-list">
        <?php foreach ($rows as $item): ?>
            <a class="continue-item" href="<?= e(url('read', ['slug' => $item['slug'], 'chapter' => (int) $item['chapter_number']])) ?>">
                <span>
                    <strong><?= e($item['title']) ?></strong><br>
                    <small>ตอนที่ <?= number_format((int) $item['chapter_number']) ?> · <?= e($item['chapter_title']) ?> · <?= e(thai_datetime((string) $item['updated_at'])) ?></small>
                </span>
                <i class="fa-solid fa-book-open" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
