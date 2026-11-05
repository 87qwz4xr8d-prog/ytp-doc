<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$action = input('action');
$id = input_int('id');

if ($action === 'delete') {
    $category = find_category($id);
    if ($category === null) {
        flash('error', 'ไม่พบหมวดหมู่');
        redirect(url('admin-categories'));
    }
    $count = db()->prepare('SELECT COUNT(*) FROM novels WHERE category_id = ?');
    $count->execute([$id]);
    if ((int) $count->fetchColumn() > 0) {
        flash('error', 'ยังลบไม่ได้ เพราะมีนิยายอยู่ในหมวดนี้');
        redirect(url('admin-categories'));
    }
    db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    flash('success', 'ลบหมวดหมู่แล้ว');
    redirect(url('admin-categories'));
}

$name = input('name');
$slugInput = input('slug');
$icon = input('icon');
$description = input('description');
$sort = input_int('sort_order');
$existing = $id > 0 ? find_category($id) : null;
remember_old($_POST);

$errors = [];
if ($name === '' || mb_strlen($name) > 80) {
    $errors[] = 'ชื่อหมวดต้องมีไม่เกิน 80 ตัวอักษร';
}
if (mb_strlen($description) > 255) {
    $errors[] = 'คำอธิบายยาวเกิน 255 ตัวอักษร';
}
if ($icon !== '' && !preg_match('/^fa-[a-z0-9-]+$/', $icon)) {
    $errors[] = 'ไอคอนต้องขึ้นต้นด้วย fa- และใช้ตัวอักษรภาษาอังกฤษพิมพ์เล็ก';
}
if ($id > 0 && $existing === null) {
    $errors[] = 'ไม่พบหมวดที่ต้องการแก้ไข';
}
if ($errors !== []) {
    flash('error', implode("\n", $errors));
    redirect(url('admin-categories', $id > 0 ? ['id' => $id] : []));
}

$slug = unique_slug('categories', slugify($slugInput !== '' ? $slugInput : $name), $id > 0 ? $id : null);
$icon = $icon !== '' ? $icon : 'fa-book';

if ($existing) {
    $stmt = db()->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, sort_order = ? WHERE id = ?');
    $stmt->execute([$name, $slug, $description, $icon, $sort, $id]);
    flash('success', 'บันทึกหมวดหมู่แล้ว');
} else {
    $stmt = db()->prepare('INSERT INTO categories (name, slug, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $slug, $description, $icon, $sort]);
    flash('success', 'เพิ่มหมวดหมู่แล้ว');
}

unset($_SESSION['old']);
redirect(url('admin-categories'));
