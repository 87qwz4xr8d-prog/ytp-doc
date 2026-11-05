<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$novelId = (int) ($_GET['novel'] ?? 0);
$novel = find_novel($novelId, true);
if ($novel === null) {
    flash('error', 'ไม่พบนิยายสำหรับจัดการตอน');
    redirect(url('admin-novels'));
}
$chapters = chapters_of($novelId, true);
$pageTitle = 'ตอนของ ' . $novel['title'];
?>
<div class="page-heading d-flex flex-wrap justify-content-between gap-3">
    <div>
        <h1>ตอนของ <?= e($novel['title']) ?></h1>
        <p><?= number_format(count($chapters)) ?> ตอน · <a href="<?= e(url('novel', ['slug' => $novel['slug']])) ?>">เปิดหน้าอ่าน</a></p>
    </div>
    <a class="btn btn-seal" href="<?= e(url('admin-chapter-form', ['novel' => $novelId])) ?>">เพิ่มตอน</a>
</div>
<?php if ($chapters === []): ?>
    <?php render_empty('fa-scroll', 'ยังไม่มีตอน', 'เพิ่มตอนแรกเพื่อให้ผู้อ่านเริ่มเรื่องได้'); ?>
<?php else: ?>
    <div class="table-responsive panel">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>ตอน</th><th>ชื่อ</th><th>ความยาว</th><th>เผยแพร่</th><th>ยอดเปิด</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($chapters as $chapter): ?>
                    <tr>
                        <td><?= number_format((int) $chapter['chapter_number']) ?></td>
                        <td><?= e($chapter['title']) ?></td>
                        <td><?= number_format((int) $chapter['content_length']) ?></td>
                        <td><?= (int) $chapter['is_published'] === 1 ? 'เปิด' : 'ฉบับร่าง' ?></td>
                        <td><?= number_format((int) $chapter['view_count']) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('read', ['slug' => $novel['slug'], 'chapter' => (int) $chapter['chapter_number']])) ?>">อ่าน</a>
                                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin-chapter-form', ['novel' => $novelId, 'id' => (int) $chapter['id']])) ?>">แก้ไข</a>
                                <form method="post" action="<?= e(url('admin-chapters', ['novel' => $novelId])) ?>" data-confirm="ลบตอนนี้หรือไม่" data-confirm-ok="ลบ">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $chapter['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm" type="submit">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
