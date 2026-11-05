<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$id = (int) ($_GET['id'] ?? 0);
$novel = $id > 0 ? find_novel($id, true) : null;
if ($id > 0 && $novel === null) {
    flash('error', 'ไม่พบนิยายที่ต้องการแก้ไข');
    redirect(url('admin-novels'));
}
$categories = all_categories();
$hasOld = isset($_SESSION['old']) && is_array($_SESSION['old']);
$selectedCategory = (string) old('category_id', $novel['category_id'] ?? '');
$selectedStatus = (string) old('status', $novel['status'] ?? 'ongoing');
$published = $hasOld ? (string) old('is_published') === '1' : (int) ($novel['is_published'] ?? 1) === 1;
$pageTitle = $novel ? 'แก้ไขนิยาย' : 'เพิ่มนิยาย';
?>
<div class="page-heading">
    <h1><?= e($pageTitle) ?></h1>
    <p>ชื่อเรื่อง หมวด และเรื่องย่อเป็นข้อมูลที่ผู้อ่านเห็นก่อนเปิดตอน</p>
</div>
<form class="panel p-3 p-md-4" method="post" action="<?= e(url('admin-novel-form', $novel ? ['id' => (int) $novel['id']] : [])) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($novel['id'] ?? 0) ?>">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="title">ชื่อเรื่อง</label>
            <input class="form-control" id="title" name="title" value="<?= e((string) old('title', $novel['title'] ?? '')) ?>" required maxlength="200">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="slug">ที่อยู่เรื่อง</label>
            <input class="form-control" id="slug" name="slug" value="<?= e((string) old('slug', $novel['slug'] ?? '')) ?>" maxlength="180" placeholder="เว้นว่างเพื่อสร้างจากชื่อ">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="author_name">ผู้แต่ง</label>
            <input class="form-control" id="author_name" name="author_name" value="<?= e((string) old('author_name', $novel['author_name'] ?? '')) ?>" required maxlength="120">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="category_id">หมวดหมู่</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">เลือกหมวด</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $selectedCategory ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status">สถานะเรื่อง</label>
            <select class="form-select" id="status" name="status">
                <?php foreach (['ongoing' => 'กำลังเขียน', 'completed' => 'จบแล้ว', 'hiatus' => 'พักเรื่อง'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label" for="synopsis">เรื่องย่อ</label>
            <textarea class="form-control" id="synopsis" name="synopsis" rows="5" required maxlength="8000"><?= e((string) old('synopsis', $novel['synopsis'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="cover">ปก (JPG, PNG, WEBP ไม่เกิน 2 MB)</label>
            <input class="form-control" id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($novel['cover_image'])): ?>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="remove_cover" name="remove_cover" value="1">
                    <label class="form-check-label" for="remove_cover">ลบปกปัจจุบัน</label>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" <?= $published ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_published">เผยแพร่ให้ผู้อ่านเห็น</label>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-seal" type="submit">บันทึก</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('admin-novels')) ?>">กลับ</a>
    </div>
</form>
