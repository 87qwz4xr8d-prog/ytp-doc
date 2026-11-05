<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$id = input_int('id');
$novel = find_novel($id, true);
if ($novel === null) {
    flash('error', 'ไม่พบนิยายที่ต้องการลบ');
    redirect(url('admin-novels'));
}

delete_cover($novel['cover_image'] ?? null);
db()->prepare('DELETE FROM novels WHERE id = ?')->execute([$id]);
flash('success', 'ลบนิยายและตอนที่เกี่ยวข้องแล้ว');
redirect(url('admin-novels'));
