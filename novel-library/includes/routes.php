<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

return [
    'home' => [
        'file' => 'home.php',
        'title' => 'หน้าแรก',
        'layout' => 'app',
    ],
    'novels' => [
        'file' => 'novels.php',
        'title' => 'นิยายทั้งหมด',
        'layout' => 'app',
    ],
    'novel' => [
        'file' => 'novel.php',
        'title' => 'รายละเอียดนิยาย',
        'layout' => 'app',
    ],
    'read' => [
        'file' => 'read.php',
        'title' => 'อ่านนิยาย',
        'layout' => 'reader',
    ],
    'category' => [
        'file' => 'category.php',
        'title' => 'หมวดหมู่',
        'layout' => 'app',
    ],
    'search' => [
        'file' => 'search.php',
        'title' => 'ค้นหานิยาย',
        'layout' => 'app',
    ],
    'login' => [
        'file' => 'login.php',
        'title' => 'เข้าสู่ระบบ',
        'layout' => 'auth',
        'post' => 'login.php',
    ],
    'register' => [
        'file' => 'register.php',
        'title' => 'สมัครสมาชิก',
        'layout' => 'auth',
        'post' => 'register.php',
    ],
    'logout' => [
        'title' => 'ออกจากระบบ',
        'layout' => 'app',
        'auth' => 'user',
        'post' => 'logout.php',
        'redirect' => 'home',
    ],
    'bookmarks' => [
        'file' => 'bookmarks.php',
        'title' => 'ชั้นหนังสือของฉัน',
        'layout' => 'app',
        'auth' => 'user',
    ],
    'history' => [
        'file' => 'history.php',
        'title' => 'อ่านค้างไว้',
        'layout' => 'app',
        'auth' => 'user',
    ],
    'profile' => [
        'file' => 'profile.php',
        'title' => 'โปรไฟล์',
        'layout' => 'app',
        'auth' => 'user',
        'post' => 'profile.php',
    ],
    'bookmark' => [
        'auth' => 'user',
        'post' => 'bookmark.php',
        'redirect' => 'bookmarks',
    ],
    'admin' => [
        'file' => 'admin/dashboard.php',
        'title' => 'แดชบอร์ด',
        'layout' => 'app',
        'auth' => 'admin',
    ],
    'admin-novels' => [
        'file' => 'admin/novels.php',
        'title' => 'จัดการนิยาย',
        'layout' => 'app',
        'auth' => 'admin',
        'post' => 'novel-delete.php',
    ],
    'admin-novel-form' => [
        'file' => 'admin/novel-form.php',
        'title' => 'บันทึกนิยาย',
        'layout' => 'app',
        'auth' => 'admin',
        'post' => 'novel-save.php',
    ],
    'admin-chapters' => [
        'file' => 'admin/chapters.php',
        'title' => 'จัดการตอน',
        'layout' => 'app',
        'auth' => 'admin',
        'post' => 'chapter-delete.php',
    ],
    'admin-chapter-form' => [
        'file' => 'admin/chapter-form.php',
        'title' => 'บันทึกตอน',
        'layout' => 'app',
        'auth' => 'admin',
        'post' => 'chapter-save.php',
    ],
    'admin-categories' => [
        'file' => 'admin/categories.php',
        'title' => 'จัดการหมวดหมู่',
        'layout' => 'app',
        'auth' => 'admin',
        'post' => 'category-action.php',
    ],
    'admin-users' => [
        'file' => 'admin/users.php',
        'title' => 'จัดการผู้ใช้',
        'layout' => 'app',
        'auth' => 'admin',
        'post' => 'user-action.php',
    ],
    '404' => [
        'file' => '404.php',
        'title' => 'ไม่พบหน้า',
        'layout' => 'app',
    ],
];
