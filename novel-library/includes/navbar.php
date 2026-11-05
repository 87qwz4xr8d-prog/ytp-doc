<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$me = current_user();
$appName = (string) (app_config()['app']['name'] ?? 'คลังนิยาย');
$searchValue = ($page ?? '') === 'search' ? (string) ($_GET['q'] ?? '') : '';
?>
<header class="topbar sticky-top">
    <div class="container-xxl">
        <div class="topbar-row">
            <?php if (($layout ?? '') === 'app'): ?>
                <button class="btn btn-nav d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                    <span class="visually-hidden">เปิดหมวดหมู่</span>
                </button>
            <?php endif; ?>

            <a class="brand" href="<?= e(url('home')) ?>">
                <span class="brand-mark" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
                <span>
                    <strong><?= e($appName) ?></strong>
                    <small>อ่านได้ทุกหน้าจอ</small>
                </span>
            </a>

            <nav class="top-links d-none d-lg-flex" aria-label="เมนูหลัก">
                <a class="<?= ($page ?? '') === 'home' ? 'is-active' : '' ?>" href="<?= e(url('home')) ?>">หน้าแรก</a>
                <a class="<?= in_array(($page ?? ''), ['novels', 'novel', 'category'], true) ? 'is-active' : '' ?>" href="<?= e(url('novels')) ?>">นิยายทั้งหมด</a>
                <?php if ($me !== null): ?>
                    <a class="<?= ($page ?? '') === 'history' ? 'is-active' : '' ?>" href="<?= e(url('history')) ?>">อ่านค้างไว้</a>
                <?php endif; ?>
            </nav>

            <div class="top-tools">
                <button class="btn btn-nav" type="button" data-theme-toggle aria-label="สลับโหมดกลางวันและกลางคืน">
                    <i class="fa-solid fa-moon" aria-hidden="true"></i>
                </button>
                <?php if ($me !== null): ?>
                    <a class="btn btn-nav<?= ($page ?? '') === 'bookmarks' ? ' is-active' : '' ?>" href="<?= e(url('bookmarks')) ?>" aria-label="ชั้นหนังสือของฉัน">
                        <i class="fa-solid fa-bookmark" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <button class="btn btn-nav d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#accountMenu" aria-expanded="false" aria-controls="accountMenu">
                    <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
                    <span class="visually-hidden">เปิดเมนูบัญชี</span>
                </button>
            </div>
        </div>

        <form class="nav-search" method="get" action="index.php" role="search">
            <input type="hidden" name="page" value="search">
            <label class="visually-hidden" for="nav-q">ค้นหานิยาย</label>
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input id="nav-q" type="search" name="q" value="<?= e($searchValue) ?>" placeholder="ค้นหาชื่อเรื่อง ผู้แต่ง หรือหมวดหมู่" maxlength="100">
            <button class="btn btn-seal btn-sm" type="submit">ค้นหา</button>
        </form>

        <div class="collapse d-lg-block" id="accountMenu">
            <div class="account-row">
                <div class="d-lg-none mobile-links">
                    <a href="<?= e(url('home')) ?>">หน้าแรก</a>
                    <a href="<?= e(url('novels')) ?>">นิยายทั้งหมด</a>
                    <?php if ($me !== null): ?>
                        <a href="<?= e(url('history')) ?>">อ่านค้างไว้</a>
                        <a href="<?= e(url('bookmarks')) ?>">ชั้นหนังสือ</a>
                    <?php endif; ?>
                </div>
                <?php if ($me === null): ?>
                    <a class="btn btn-outline-gold btn-sm" href="<?= e(url('register')) ?>">สมัครสมาชิก</a>
                    <a class="btn btn-seal btn-sm" href="<?= e(url('login')) ?>">เข้าสู่ระบบ</a>
                <?php else: ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-gold btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            <?= e($me['display_name']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= e(url('profile')) ?>"><i class="fa-solid fa-id-card"></i> โปรไฟล์</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('bookmarks')) ?>"><i class="fa-solid fa-bookmark"></i> ชั้นหนังสือ</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('history')) ?>"><i class="fa-solid fa-clock-rotate-left"></i> อ่านค้างไว้</a></li>
                            <?php if ($me['role'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= e(url('admin')) ?>"><i class="fa-solid fa-gauge"></i> จัดการระบบ</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= e(url('logout')) ?>" data-confirm="ออกจากระบบตอนนี้หรือไม่" data-confirm-ok="ออกจากระบบ" data-confirm-icon="question">
                                    <?= csrf_field() ?>
                                    <button class="dropdown-item" type="submit"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
