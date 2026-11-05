<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$keyword = trim((string) ($_GET['q'] ?? ''));
$keyword = mb_substr($keyword, 0, 100);
$pageTitle = $keyword === '' ? 'ค้นหานิยาย' : 'ผลการค้นหา: ' . $keyword;
$result = null;
if ($keyword !== '') {
    $result = list_novels([
        'q' => $keyword,
        'page' => (int) ($_GET['p'] ?? 1),
        'sort' => 'new',
    ]);
}
?>
<div class="page-heading">
    <h1>ค้นหานิยาย</h1>
    <p>ค้นจากชื่อเรื่อง ชื่อผู้แต่ง เรื่องย่อ หรือชื่อหมวดหมู่</p>
</div>
<form class="filter-bar" method="get" action="index.php" role="search">
    <input type="hidden" name="page" value="search">
    <label class="visually-hidden" for="search-q">คำค้น</label>
    <input class="form-control" id="search-q" type="search" name="q" value="<?= e($keyword) ?>" maxlength="100" placeholder="พิมพ์คำค้น">
    <button class="btn btn-seal" type="submit">ค้นหา</button>
</form>
<?php if ($keyword === ''): ?>
    <?php render_empty('fa-magnifying-glass', 'เริ่มจากคำที่จำได้', 'เช่น ชื่อเมือง ชื่อตัวละคร หรือหมวดแฟนตาซี'); ?>
<?php elseif ($result['rows'] === []): ?>
    <?php render_empty('fa-magnifying-glass', 'ไม่พบเรื่องที่ตรงกับคำค้น', 'ลองใช้คำที่สั้นลง หรือดูนิยายทั้งหมด'); ?>
<?php else: ?>
    <p>พบ <?= number_format($result['total']) ?> เรื่องสำหรับ “<?= e($keyword) ?>”</p>
    <div class="novel-grid">
        <?php foreach ($result['rows'] as $novel): ?>
            <?php include APP_ROOT . '/includes/partials/novel-card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php render_pagination($result['page'], $result['pages'], ['page' => 'search', 'q' => $keyword]); ?>
<?php endif; ?>
