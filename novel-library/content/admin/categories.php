<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$editId = (int) ($_GET['id'] ?? 0);
$editing = $editId > 0 ? find_category($editId) : null;
$categories = categories_with_counts();
?>
<div class="page-heading">
    <h1>หมวดหมู่</h1>
    <p>ไอคอนใช้ชื่อคลาสของ Font Awesome เช่น <code>fa-book</code></p>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <form class="panel p-3" method="post" action="<?= e(url('admin-categories', $editing ? ['id' => (int) $editing['id']] : [])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <h2 class="h5"><?= $editing ? 'แก้ไขหมวด' : 'เพิ่มหมวด' ?></h2>
            <div class="mb-3">
                <label class="form-label" for="name">ชื่อหมวด</label>
                <input class="form-control" id="name" name="name" value="<?= e((string) old('name', $editing['name'] ?? '')) ?>" required maxlength="80">
            </div>
            <div class="mb-3">
                <label class="form-label" for="slug">ที่อยู่</label>
                <input class="form-control" id="slug" name="slug" value="<?= e((string) old('slug', $editing['slug'] ?? '')) ?>" maxlength="120" placeholder="เว้นว่างเพื่อสร้างจากชื่อ">
            </div>
            <div class="mb-3">
                <label class="form-label" for="icon">ไอคอน</label>
                <input class="form-control" id="icon" name="icon" value="<?= e((string) old('icon', $editing['icon'] ?? 'fa-book')) ?>" maxlength="60">
            </div>
            <div class="mb-3">
                <label class="form-label" for="sort_order">ลำดับ</label>
                <input class="form-control" id="sort_order" name="sort_order" type="number" value="<?= e((string) old('sort_order', $editing['sort_order'] ?? 0)) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">คำอธิบาย</label>
                <textarea class="form-control" id="description" name="description" rows="3" maxlength="255"><?= e((string) old('description', $editing['description'] ?? '')) ?></textarea>
            </div>
            <button class="btn btn-seal" type="submit">บันทึกหมวด</button>
        </form>
    </div>
    <div class="col-lg-7">
        <div class="table-responsive panel">
            <table class="table align-middle mb-0">
                <thead><tr><th></th><th>ชื่อ</th><th>เรื่อง</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><i class="<?= e(icon_class((string) $category['icon'])) ?>"></i></td>
                            <td><?= e($category['name']) ?><div class="meta"><?= e($category['slug']) ?></div></td>
                            <td><?= number_format((int) $category['novel_count']) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin-categories', ['id' => (int) $category['id']])) ?>">แก้ไข</a>
                                    <form method="post" action="<?= e(url('admin-categories')) ?>" data-confirm="ลบหมวดนี้หรือไม่" data-confirm-ok="ลบ">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
