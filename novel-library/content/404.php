<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

render_empty('fa-compass', 'ไม่พบหน้าที่ต้องการ', 'ลิงก์อาจหมดอายุ หรือพิมพ์ที่อยู่ไม่ครบ');
echo '<p class="text-center"><a class="btn btn-seal" href="' . e(url('home')) . '">กลับหน้าแรก</a></p>';
