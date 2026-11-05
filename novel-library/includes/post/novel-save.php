<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$id = input_int('id');
$title = input('title');
$author = input('author_name');
$categoryId = input_int('category_id');
$status = input('status');
$synopsis = input('synopsis');
$slugInput = input('slug');
$published = isset($_POST['is_published']) ? 1 : 0;
$existing = $id > 0 ? find_novel($id, true) : null;
$back = url('admin-novel-form', $id > 0 ? ['id' => $id] : []);

$old = $_POST;
$old['is_published'] = $published ? '1' : '';
remember_old($old);

$errors = [];
if ($title === '' || mb_strlen($title) > 200) {
    $errors[] = 'ชื่อเรื่องต้องมีไม่เกิน 200 ตัวอักษร';
}
if ($author === '' || mb_strlen($author) > 120) {
    $errors[] = 'ชื่อผู้แต่งต้องมีไม่เกิน 120 ตัวอักษร';
}
if (!in_array($status, ['ongoing', 'completed', 'hiatus'], true)) {
    $errors[] = 'สถานะเรื่องไม่ถูกต้อง';
}
if ($synopsis === '' || mb_strlen($synopsis) > 8000) {
    $errors[] = 'เรื่องย่อต้องมีและไม่เกิน 8,000 ตัวอักษร';
}
if ($categoryId <= 0 || find_category($categoryId) === null) {
    $errors[] = 'เลือกหมวดหมู่ที่มีอยู่ในระบบ';
}
if ($id > 0 && $existing === null) {
    $errors[] = 'ไม่พบนิยายที่ต้องการแก้ไข';
}
if ($slugInput !== '' && mb_strlen(slugify($slugInput)) > 180) {
    $errors[] = 'ที่อยู่เรื่องยาวเกินไป';
}

$coverName = $existing['cover_image'] ?? null;
$uploaded = null;
if ($errors === [] && !empty($_FILES['cover']) && is_array($_FILES['cover'])) {
    $stored = store_cover($_FILES['cover']);
    if ($stored['error'] !== null) {
        $errors[] = $stored['error'];
    }
    $uploaded = $stored['name'];
}

if ($errors !== []) {
    flash('error', implode("\n", $errors));
    redirect($back);
}

if (isset($_POST['remove_cover']) && $uploaded === null) {
    delete_cover(is_string($coverName) ? $coverName : null);
    $coverName = null;
}
if (is_string($uploaded) && $uploaded !== '') {
    delete_cover(is_string($coverName) ? $coverName : null);
    $coverName = $uploaded;
}

$baseSlug = slugify($slugInput !== '' ? $slugInput : $title);
$slug = unique_slug('novels', $baseSlug, $id > 0 ? $id : null);
$me = current_user();

if ($existing) {
    $stmt = db()->prepare(
        'UPDATE novels
         SET title = ?, slug = ?, author_name = ?, category_id = ?, synopsis = ?, cover_image = ?, status = ?, is_published = ?
         WHERE id = ?'
    );
    $stmt->execute([$title, $slug, $author, $categoryId, $synopsis, $coverName, $status, $published, $id]);
    $novelId = $id;
    flash('success', 'บันทึกนิยายแล้ว');
} else {
    $stmt = db()->prepare(
        'INSERT INTO novels (title, slug, author_name, category_id, synopsis, cover_image, status, is_published, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $slug, $author, $categoryId, $synopsis, $coverName, $status, $published, (int) $me['id']]);
    $novelId = (int) db()->lastInsertId();
    flash('success', 'เพิ่มนิยายแล้ว ต่อไปเพิ่มตอนแรกได้เลย');
}

unset($_SESSION['old']);
redirect(url('admin-chapters', ['novel' => $novelId]));
