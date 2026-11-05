<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$users = all_users();
$me = current_user();
?>
<div class="page-heading">
    <h1>ผู้ใช้</h1>
    <p>เปลี่ยนสิทธิ์ได้ระหว่างผู้ดูแลกับนักอ่าน และลบบัญชีที่ไม่ใช้บัญชีของตนเอง</p>
</div>
<div class="table-responsive panel">
    <table class="table align-middle mb-0">
        <thead>
            <tr><th>ชื่อ</th><th>อีเมล</th><th>สมัครเมื่อ</th><th>สิทธิ์</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['display_name']) ?><div class="meta">@<?= e($user['username']) ?></div></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e(thai_datetime((string) $user['created_at'])) ?></td>
                    <td>
                        <?php if ((int) $user['id'] === (int) $me['id']): ?>
                            <?= $user['role'] === 'admin' ? 'ผู้ดูแล (บัญชีนี้)' : 'นักอ่าน (บัญชีนี้)' ?>
                        <?php else: ?>
                            <form class="d-flex gap-2" method="post" action="<?= e(url('admin-users')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="role">
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <label class="visually-hidden" for="role-<?= (int) $user['id'] ?>">สิทธิ์ของ <?= e($user['username']) ?></label>
                                <select class="form-select form-select-sm" id="role-<?= (int) $user['id'] ?>" name="role">
                                    <option value="member" <?= $user['role'] === 'member' ? 'selected' : '' ?>>นักอ่าน</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>ผู้ดูแล</option>
                                </select>
                                <button class="btn btn-outline-secondary btn-sm" type="submit">บันทึก</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $user['id'] !== (int) $me['id']): ?>
                            <form method="post" action="<?= e(url('admin-users')) ?>" data-confirm="ลบบัญชีนี้หรือไม่" data-confirm-ok="ลบ">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit">ลบ</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
