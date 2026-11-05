<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$id = input_int('id');
$novelId = input_int('novel_id');
$number = input_int('chapter_number');
$title = input('title');
$content = (string) ($_POST['content'] ?? '');
$content = str_replace(["\r\n", "\r"], "\n", trim($content));
$published = isset($_POST['is_published']) ? 1 : 0;
$novel = find_novel($novelId, true);
$existing = $id > 0 ? find_chapter($id) : null;
$back = url('admin-chapter-form', ['novel' => $novelId, 'id' => $id > 0 ? $id : null]);

$old = $_POST;
$old['content'] = $content;
$old['is_published'] = $published ? '1' : '';
remember_old($old);

$errors = [];
if ($novel === null) {
    $errors[] = 'ไม่พบนิยายของตอนนี้';
}
if ($id > 0 && ($existing === null || (int) $existing['novel_id'] !== $novelId)) {
    $errors[] = 'ไม่พบตอนที่ต้องการแก้ไข';
}
if ($number < 1 || $number > 10000) {
    $errors[] = 'เลขตอนต้องอยู่ระหว่าง 1 ถึง 10000';
}
if ($title === '' || mb_strlen($title) > 200) {
    $errors[] = 'ชื่อตอนต้องมีไม่เกิน 200 ตัวอักษร';
}
if ($content === '' || mb_strlen($content) > 100000) {
    $errors[] = 'เนื้อหาต้องมี และยาวไม่เกิน 100,000 ตัวอักษร';
}
if ($novel !== null && $number >= 1) {
    $dup = db()->prepare('SELECT id FROM chapters WHERE novel_id = ? AND chapter_number = ? AND id <> ?');
    $dup->execute([$novelId, $number, $id]);
    if ($dup->fetch()) {
        $errors[] = 'เลขตอนนี้มีอยู่แล้วในเรื่องเดียวกัน';
    }
}

if ($errors !== []) {
    flash('error', implode("\n", $errors));
    redirect($back);
}

if ($existing) {
    $stmt = db()->prepare(
        'UPDATE chapters SET chapter_number = ?, title = ?, content = ?, is_published = ? WHERE id = ?'
    );
    $stmt->execute([$number, $title, $content, $published, $id]);
    flash('success', 'บันทึกตอนแล้ว');
} else {
    $stmt = db()->prepare(
        'INSERT INTO chapters (novel_id, chapter_number, title, content, is_published) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$novelId, $number, $title, $content, $published]);
    flash('success', 'เพิ่มตอนแล้ว');
}

touch_novel($novelId);
unset($_SESSION['old']);
redirect(url('admin-chapters', ['novel' => $novelId]));
