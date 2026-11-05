<?php

declare(strict_types=1);

/**
 * การตั้งค่าหลักของคลังนิยาย
 * แก้ค่าในไฟล์นี้ให้ตรงกับฐานข้อมูลที่สร้างไว้ใน phpMyAdmin
 * หรือกำหนดผ่านตัวแปรสภาพแวดล้อม NOVEL_DB_* เมื่อนำไปวางบนเซิร์ฟเวอร์
 */
return [
    'db' => [
        'host' => getenv('NOVEL_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('NOVEL_DB_PORT') ?: 3306),
        'name' => getenv('NOVEL_DB_NAME') ?: 'novel_library',
        'user' => getenv('NOVEL_DB_USER') ?: 'novel_user',
        'pass' => getenv('NOVEL_DB_PASS') ?: 'NovelLib@2026',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'คลังนิยาย',
        'tagline' => 'จัดเก็บ อ่าน และเปิดต่อได้บนมือถือ แท็บเล็ต และคอมพิวเตอร์',
        'timezone' => 'Asia/Bangkok',
        'session_name' => 'klangniyai',
        'debug' => false,
        'show_demo_accounts' => true,
        'per_page' => 9,
    ],
];
