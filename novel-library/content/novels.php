<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$allowedStatus = ['', 'ongoing', 'completed', 'hiatus'];
$allowedSort = ['new', 'popular', 'title'];
$status = (string) ($_GET['status'] ?? '');
$sort = (string) ($_GET['sort'] ?? 'new');
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'new';
}

$result = list_novels([
    'status' => $status,
    'sort' => $sort,
    'page' => (int) ($_GET['p'] ?? 1),
]);
$query = ['page' => 'novels', 'status' => $status, 'sort' => $sort];
?>
<div class="page-heading">
    <h1>นิยายทั้งหมด</h1>
    <p>พบ <?= number_format($result['total']) ?> เรื่องที่เปิดให้อ่าน</p>
</div>
<div class="filter-bar" role="toolbar" aria-label="ตัวกรอง">
    <?php
    $statuses = ['' => 'ทุกสถานะ', 'ongoing' => 'กำลังเขียน', 'completed' => 'จบแล้ว', 'hiatus' => 'พักเรื่อง'];
    foreach ($statuses as $value => $label):
        $active = $status === $value ? ' is-active' : '';
        ?>
        <a class="chip<?= $active ?>" href="<?= e(url('novels', ['status' => $value, 'sort' => $sort])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php
    $sorts = ['new' => 'มาใหม่', 'popular' => 'ยอดนิยม', 'title' => 'ชื่อเรื่อง'];
    foreach ($sorts as $value => $label):
        $active = $sort === $value ? ' is-active' : '';
        ?>
        <a class="chip<?= $active ?>" href="<?= e(url('novels', ['status' => $status, 'sort' => $value])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>
<?php if ($result['rows'] === []): ?>
    <?php render_empty('fa-book-open', 'ไม่พบนิยายในตัวกรองนี้', 'ลองเลือกสถานะอื่น หรือดูนิยายทั้งหมด'); ?>
<?php else: ?>
    <div class="novel-grid">
        <?php foreach ($result['rows'] as $novel): ?>
            <?php include APP_ROOT . '/includes/partials/novel-card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php render_pagination($result['page'], $result['pages'], $query); ?>
<?php endif; ?>
