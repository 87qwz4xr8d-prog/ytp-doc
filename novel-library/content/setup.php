<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

/** @var string $dbError */
?>
<section class="auth-card">
    <p class="eyebrow"><i class="fa-solid fa-database" aria-hidden="true"></i> ยังเชื่อมฐานข้อมูลไม่ได้</p>
    <h1 class="h3">ตั้งค่า phpMyAdmin ก่อนเปิดคลัง</h1>
    <ol>
        <li>สร้างฐานข้อมูล <code>novel_library</code> ด้วยชุดอักขระ <code>utf8mb4_unicode_ci</code></li>
        <li>นำเข้าไฟล์ <code>sql/schema.sql</code> จากแท็บ Import</li>
        <li>สร้างผู้ใช้ฐานข้อมูล แล้วกรอกค่าใน <code>config/config.php</code></li>
        <li>เปิดโฟลเดอร์ <code>uploads/covers</code> ให้เว็บเซิร์ฟเวอร์เขียนไฟล์ได้</li>
    </ol>
    <p class="mb-0">รายละเอียดทีละขั้นอยู่ใน <code>novel-library/README.md</code></p>
</section>
