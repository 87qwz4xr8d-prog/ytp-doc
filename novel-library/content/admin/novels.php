<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$keyword = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$result = list_novels([
    'q' => $keyword,
    'include_unpublished' => true,
    'page' => (int) ($_GET['p'] ?? 1),
    'per_page' => 12,
]);
?>
<div class="page-heading d-flex flex-wrap justify-content-between gap-3">
    <div>
        <h1>จัดการนิยาย</h1>
        <p>เรื่องที่ยังไม่เผยแพร่จะไม่โผล่ในหน้าอ่านของสมาชิก</p>
    </div>
    <a class="btn btn-seal" href="<?= e(url('admin-novel-form')) ?>">เพิ่มนิยาย</a>
</div>
<form class="filter-bar" method="get" action="index.php">
    <input type="hidden" name="page" value="admin-novels">
    <label class="visually-hidden" for="admin-q">ค้นหาในระบบ</label>
    <input class="form-control" id="admin-q" type="search" name="q" value="<?= e($keyword) ?>" placeholder="ค้นชื่อเรื่องหรือผู้แต่ง">
    <button class="btn btn-seal" type="submit">ค้นหา</button>
</form>
<div class="table-responsive panel">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>ปก</th><th>เรื่อง</th><th>หมวด</th><th>สถานะ</th><th>เผยแพร่</th><th>ตอน</th><th>ยอดเปิด</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result['rows'] as $novel): ?>
                <tr>
                    <td>
                        <div class="cover-thumb" style="--hue: <?= cover_hue((string) $novel['slug']) ?>">
                            <?php if (!empty($novel['cover_image'])): ?>
                                <img src="<?= e(upload_url((string) $novel['cover_image'])) ?>" alt="">
                            <?php else: ?>
                                <i class="fa-solid fa-book"></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <a href="<?= e(url('novel', ['slug' => $novel['slug']])) ?>"><?= e($novel['title']) ?></a>
                        <div class="meta"><?= e($novel['author_name']) ?></div>
                    </td>
                    <td><?= e($novel['category_name']) ?></td>
                    <td><span class="status-pill status-<?= e((string) $novel['status']) ?>"><?= e(novel_status_label((string) $novel['status'])) ?></span></td>
                    <td><?= (int) $novel['is_published'] === 1 ? 'เปิด' : 'ปิด' ?></td>
                    <td><?= number_format((int) $novel['chapter_total']) ?></td>
                    <td><?= number_format((int) $novel['view_count']) ?></td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin-chapters', ['novel' => (int) $novel['id']])) ?>">ตอน</a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin-novel-form', ['id' => (int) $novel['id']])) ?>">แก้ไข</a>
                            <form method="post" action="<?= e(url('admin-novels', array_filter(['q' => $keyword, 'p' => $result['page'] > 1 ? $result['page'] : null]))) ?>" data-confirm="ลบเรื่องนี้พร้อมตอนทั้งหมดหรือไม่" data-confirm-ok="ลบ">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $novel['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit">ลบ</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php render_pagination($result['page'], $result['pages'], ['page' => 'admin-novels', 'q' => $keyword]); ?>
