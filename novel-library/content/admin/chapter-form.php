<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$novelId = (int) ($_GET['novel'] ?? old('novel_id', 0));
$chapterId = (int) ($_GET['id'] ?? 0);
$novel = find_novel($novelId, true);
if ($novel === null) {
    flash('error', 'ไม่พบนิยายสำหรับบันทึกตอน');
    redirect(url('admin-novels'));
}
$chapter = $chapterId > 0 ? find_chapter($chapterId) : null;
if ($chapterId > 0 && ($chapter === null || (int) $chapter['novel_id'] !== $novelId)) {
    flash('error', 'ไม่พบตอนที่ต้องการแก้ไข');
    redirect(url('admin-chapters', ['novel' => $novelId]));
}
$hasOld = isset($_SESSION['old']) && is_array($_SESSION['old']);
$published = $hasOld ? (string) old('is_published') === '1' : (int) ($chapter['is_published'] ?? 1) === 1;
$number = (string) old('chapter_number', $chapter['chapter_number'] ?? next_chapter_number($novelId));
$pageTitle = $chapter ? 'แก้ไขตอน' : 'เพิ่มตอน';
?>
<div class="page-heading">
    <h1><?= e($pageTitle) ?></h1>
    <p><?= e($novel['title']) ?> · เว้นบรรทัดว่างหนึ่งบรรทัดเพื่อขึ้นย่อหน้าใหม่</p>
</div>
<form class="panel p-3 p-md-4" method="post" action="<?= e(url('admin-chapter-form', ['novel' => $novelId, 'id' => $chapterId ?: null])) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($chapter['id'] ?? 0) ?>">
    <input type="hidden" name="novel_id" value="<?= $novelId ?>">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="chapter_number">เลขตอน</label>
            <input class="form-control" id="chapter_number" name="chapter_number" type="number" min="1" max="10000" value="<?= e($number) ?>" required>
        </div>
        <div class="col-md-9">
            <label class="form-label" for="title">ชื่อตอน</label>
            <input class="form-control" id="title" name="title" value="<?= e((string) old('title', $chapter['title'] ?? '')) ?>" required maxlength="200">
        </div>
        <div class="col-12">
            <label class="form-label" for="content">เนื้อหา</label>
            <textarea class="form-control" id="content" name="content" rows="16" required><?= e((string) old('content', $chapter['content'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" <?= $published ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_published">เผยแพร่ตอนนี้</label>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-seal" type="submit">บันทึกตอน</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('admin-chapters', ['novel' => $novelId])) ?>">กลับ</a>
    </div>
</form>
