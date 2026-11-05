<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$stats = dashboard_stats();
$recent = recent_chapters(6);
?>
<div class="page-heading d-flex flex-wrap justify-content-between gap-3">
    <div>
        <h1>แดชบอร์ด</h1>
        <p>ภาพรวมของคลังนิยาย</p>
    </div>
    <a class="btn btn-seal" href="<?= e(url('admin-novel-form')) ?>"><i class="fa-solid fa-plus"></i> เพิ่มนิยาย</a>
</div>
<div class="stat-grid">
    <a class="stat-card" href="<?= e(url('admin-novels')) ?>"><span>นิยายทั้งหมด</span><strong><?= number_format($stats['novels']) ?></strong></a>
    <a class="stat-card" href="<?= e(url('admin-novels')) ?>"><span>เผยแพร่แล้ว</span><strong><?= number_format($stats['published']) ?></strong></a>
    <a class="stat-card" href="<?= e(url('admin-users')) ?>"><span>ผู้ใช้</span><strong><?= number_format($stats['users']) ?></strong></a>
    <a class="stat-card" href="<?= e(url('novels', ['sort' => 'popular'])) ?>"><span>ยอดเปิดเรื่อง</span><strong><?= number_format($stats['views']) ?></strong></a>
    <a class="stat-card" href="<?= e(url('admin-categories')) ?>"><span>ตอน</span><strong><?= number_format($stats['chapters']) ?></strong></a>
    <a class="stat-card" href="<?= e(url('admin')) ?>"><span>การเก็บเข้าชั้นหนังสือ</span><strong><?= number_format($stats['bookmarks']) ?></strong></a>
</div>
<div class="section-head">
    <h2>ตอนที่แก้ล่าสุด</h2>
</div>
<div class="table-responsive panel">
    <table class="table align-middle mb-0">
        <thead>
            <tr><th>เรื่อง</th><th>ตอน</th><th>สถานะ</th><th>แก้ล่าสุด</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $chapter): ?>
                <tr>
                    <td><?= e($chapter['novel_title']) ?></td>
                    <td>ตอนที่ <?= number_format((int) $chapter['chapter_number']) ?> · <?= e($chapter['title']) ?></td>
                    <td><?= (int) $chapter['is_published'] === 1 ? 'เผยแพร่' : 'ฉบับร่าง' ?></td>
                    <td><?= e(thai_datetime((string) $chapter['updated_at'])) ?></td>
                    <td><a href="<?= e(url('admin-chapter-form', ['novel' => (int) $chapter['novel_id'], 'id' => (int) $chapter['id']])) ?>">แก้ไข</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
