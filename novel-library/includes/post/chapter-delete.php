<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$chapter = find_chapter(input_int('id'));
if ($chapter === null) {
    flash('error', 'ไม่พบตอนที่ต้องการลบ');
    redirect(url('admin-novels'));
}

$novelId = (int) $chapter['novel_id'];
db()->prepare('DELETE FROM chapters WHERE id = ?')->execute([(int) $chapter['id']]);
touch_novel($novelId);
flash('success', 'ลบตอนแล้ว');
redirect(url('admin-chapters', ['novel' => $novelId]));
