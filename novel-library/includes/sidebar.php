<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

if (!isset($sidebarData)) {
    $sidebarData = [
        'categories' => categories_with_counts(),
        'latest' => latest_novels(5),
    ];
}

if (!function_exists('render_sidebar_menu')) {
    function render_sidebar_menu(array $sidebarData): void
    {
        global $page;
        $currentCategory = ($page ?? '') === 'category' ? (string) ($_GET['slug'] ?? '') : '';
        $status = ($page ?? '') === 'novels' ? (string) ($_GET['status'] ?? '') : '';
        $me = current_user();
        ?>
        <p class="sidebar-kicker">ห้องสมุด</p>
        <nav class="sidebar-nav" aria-label="ทางลัดในคลัง">
            <a class="<?= $status === '' && ($page ?? '') === 'novels' ? 'is-active' : '' ?>" href="<?= e(url('novels')) ?>">
                <i class="fa-solid fa-layer-group" aria-hidden="true"></i> นิยายทั้งหมด
            </a>
            <a class="<?= $status === 'ongoing' ? 'is-active' : '' ?>" href="<?= e(url('novels', ['status' => 'ongoing'])) ?>">
                <i class="fa-solid fa-pen-nib" aria-hidden="true"></i> กำลังเขียน
            </a>
            <a class="<?= $status === 'completed' ? 'is-active' : '' ?>" href="<?= e(url('novels', ['status' => 'completed'])) ?>">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> จบแล้ว
            </a>
        </nav>

        <p class="sidebar-kicker">หมวดหมู่</p>
        <nav class="sidebar-nav" aria-label="หมวดหมู่">
            <?php foreach ($sidebarData['categories'] as $category): ?>
                <a class="<?= $currentCategory === $category['slug'] ? 'is-active' : '' ?>" href="<?= e(url('category', ['slug' => $category['slug']])) ?>">
                    <i class="<?= e(icon_class((string) $category['icon'])) ?>" aria-hidden="true"></i>
                    <span><?= e($category['name']) ?></span>
                    <em><?= number_format((int) $category['novel_count']) ?></em>
                </a>
            <?php endforeach; ?>
        </nav>

        <p class="sidebar-kicker">อัปเดตล่าสุด</p>
        <ul class="latest-list">
            <?php foreach ($sidebarData['latest'] as $novel): ?>
                <li>
                    <a href="<?= e(url('novel', ['slug' => $novel['slug']])) ?>">
                        <strong><?= e($novel['title']) ?></strong>
                        <small><?= e($novel['category_name']) ?> · <?= e(thai_datetime((string) $novel['updated_at'])) ?></small>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($me !== null && $me['role'] === 'admin'): ?>
            <p class="sidebar-kicker">จัดการระบบ</p>
            <nav class="sidebar-nav" aria-label="เมนูผู้ดูแล">
                <a class="<?= ($page ?? '') === 'admin' ? 'is-active' : '' ?>" href="<?= e(url('admin')) ?>"><i class="fa-solid fa-gauge"></i> แดชบอร์ด</a>
                <a class="<?= in_array(($page ?? ''), ['admin-novels', 'admin-novel-form', 'admin-chapters', 'admin-chapter-form'], true) ? 'is-active' : '' ?>" href="<?= e(url('admin-novels')) ?>"><i class="fa-solid fa-book"></i> นิยายและตอน</a>
                <a class="<?= ($page ?? '') === 'admin-categories' ? 'is-active' : '' ?>" href="<?= e(url('admin-categories')) ?>"><i class="fa-solid fa-tags"></i> หมวดหมู่</a>
                <a class="<?= ($page ?? '') === 'admin-users' ? 'is-active' : '' ?>" href="<?= e(url('admin-users')) ?>"><i class="fa-solid fa-users"></i> ผู้ใช้</a>
            </nav>
        <?php endif; ?>
        <?php
    }
}

$mode = $sidebarMode ?? 'desktop';
if ($mode === 'offcanvas'): ?>
    <div class="offcanvas offcanvas-start sidebar-offcanvas" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title" id="sidebarOffcanvasLabel">สารบัญคลัง</h2>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="ปิด"></button>
        </div>
        <div class="offcanvas-body">
            <?php render_sidebar_menu($sidebarData); ?>
        </div>
    </div>
<?php else: ?>
    <aside class="sidebar" aria-label="สารบัญ">
        <?php render_sidebar_menu($sidebarData); ?>
    </aside>
<?php endif; ?>
