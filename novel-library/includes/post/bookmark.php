<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$me = current_user();
$novelId = input_int('novel_id');
$back = input('back');
$target = is_internal_app_url($back) ? $back : url('bookmarks');
$novel = find_novel($novelId, false);

if ($novel === null || (int) $novel['is_published'] !== 1) {
    flash('error', 'ไม่พบนิยายที่ต้องการเก็บ');
    redirect($target);
}

$saved = toggle_bookmark((int) $me['id'], $novelId);
flash('success', $saved ? 'เก็บเข้าชั้นหนังสือแล้ว' : 'นำออกจากชั้นหนังสือแล้ว');
redirect($target);
