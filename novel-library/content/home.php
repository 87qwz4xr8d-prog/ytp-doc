<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$counts = catalog_counts();
$latest = latest_novels(6);
$popular = popular_novels(4);
$categories = categories_with_counts();
$me = current_user();
$continue = $me ? reading_history((int) $me['id'], 3) : [];
?>
<section class="hero">
    <div>
        <p class="eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> ห้องสมุดนิยายส่วนตัว</p>
        <h1>เก็บเรื่องราวไว้ในที่เดียว อ่านต่อได้ทุกหน้าจอ</h1>
        <p>คลังนิยายจัดหมวด เก็บตอน และจำหน้าที่อ่านค้างไว้ ไม่ว่าจะเปิดจากมือถือ แท็บเล็ต หรือคอมพิวเตอร์</p>
        <form class="hero-search" method="get" action="index.php" role="search">
            <input type="hidden" name="page" value="search">
            <label class="visually-hidden" for="home-q">ค้นหาจากหน้าแรก</label>
            <input id="home-q" type="search" name="q" placeholder="ลองค้นคำว่า หลังคา หรือ จดหมาย" maxlength="100">
            <button class="btn btn-seal" type="submit">ค้นหา</button>
        </form>
    </div>
    <dl class="hero-stats">
        <div><dt>เรื่องที่เปิดอ่าน</dt><dd><?= number_format($counts['novels']) ?></dd></div>
        <div><dt>ตอนทั้งหมด</dt><dd><?= number_format($counts['chapters']) ?></dd></div>
        <div><dt>หมวดหมู่</dt><dd><?= number_format($counts['categories']) ?></dd></div>
    </dl>
</section>

<?php if ($continue !== []): ?>
    <div class="section-head">
        <h2>อ่านต่อจากหน้าที่ค้าง</h2>
        <a href="<?= e(url('history')) ?>">ดูทั้งหมด</a>
    </div>
    <div class="continue-list">
        <?php foreach ($continue as $item): ?>
            <a class="continue-item" href="<?= e(url('read', ['slug' => $item['slug'], 'chapter' => (int) $item['chapter_number']])) ?>">
                <span>
                    <strong><?= e($item['title']) ?></strong><br>
                    <small>ตอนที่ <?= number_format((int) $item['chapter_number']) ?> · <?= e($item['chapter_title']) ?></small>
                </span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section-head">
    <h2>หมวดหมู่</h2>
    <a href="<?= e(url('novels')) ?>">ดูนิยายทั้งหมด</a>
</div>
<div class="cat-scroll">
    <?php foreach ($categories as $category): ?>
        <a class="cat-tile" href="<?= e(url('category', ['slug' => $category['slug']])) ?>">
            <i class="<?= e(icon_class((string) $category['icon'])) ?>" aria-hidden="true"></i>
            <strong><?= e($category['name']) ?></strong>
            <small><?= number_format((int) $category['novel_count']) ?> เรื่อง</small>
        </a>
    <?php endforeach; ?>
</div>

<div class="section-head">
    <h2>อัปเดตล่าสุด</h2>
    <a href="<?= e(url('novels', ['sort' => 'new'])) ?>">เรียงตามวันที่</a>
</div>
<?php if ($latest === []): ?>
    <?php render_empty('fa-book', 'ยังไม่มีนิยาย', 'เมื่อผู้ดูแลเพิ่มเรื่อง นิยายจะปรากฏที่นี่'); ?>
<?php else: ?>
    <div class="novel-grid">
        <?php foreach ($latest as $novel): ?>
            <?php include APP_ROOT . '/includes/partials/novel-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section-head">
    <h2>มีคนเปิดอ่านบ่อย</h2>
    <a href="<?= e(url('novels', ['sort' => 'popular'])) ?>">ดูอันดับ</a>
</div>
<div class="novel-grid">
    <?php foreach ($popular as $novel): ?>
        <?php include APP_ROOT . '/includes/partials/novel-card.php'; ?>
    <?php endforeach; ?>
</div>
