<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$category = $slug === '' ? null : find_category_by_slug($slug);
if ($category === null) {
    http_response_code(404);
    $pageTitle = 'ไม่พบหมวดหมู่';
    render_empty('fa-tags', 'ไม่พบหมวดหมู่นี้', 'เลือกหมวดจากสารบัญด้านข้าง หรือจากหน้าแรก');
    return;
}

$sort = (string) ($_GET['sort'] ?? 'new');
if (!in_array($sort, ['new', 'popular', 'title'], true)) {
    $sort = 'new';
}
$result = list_novels([
    'category' => $category['slug'],
    'sort' => $sort,
    'page' => (int) ($_GET['p'] ?? 1),
]);
$pageTitle = (string) $category['name'];
?>
<div class="page-heading">
    <p class="eyebrow"><i class="<?= e(icon_class((string) $category['icon'])) ?>" aria-hidden="true"></i> หมวดหมู่</p>
    <h1><?= e($category['name']) ?></h1>
    <p><?= e((string) $category['description']) ?> · <?= number_format($result['total']) ?> เรื่อง</p>
</div>
<div class="filter-bar">
    <?php foreach (['new' => 'มาใหม่', 'popular' => 'ยอดนิยม', 'title' => 'ชื่อเรื่อง'] as $value => $label): ?>
        <a class="chip<?= $sort === $value ? ' is-active' : '' ?>" href="<?= e(url('category', ['slug' => $category['slug'], 'sort' => $value])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>
<?php if ($result['rows'] === []): ?>
    <?php render_empty('fa-book', 'หมวดนี้ยังไม่มีเรื่องที่เผยแพร่', 'เรื่องที่ยังเป็นฉบับร่างจะไม่แสดงต่อผู้อ่าน'); ?>
<?php else: ?>
    <div class="novel-grid">
        <?php foreach ($result['rows'] as $novel): ?>
            <?php include APP_ROOT . '/includes/partials/novel-card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php render_pagination($result['page'], $result['pages'], ['page' => 'category', 'slug' => $category['slug'], 'sort' => $sort]); ?>
<?php endif; ?>
