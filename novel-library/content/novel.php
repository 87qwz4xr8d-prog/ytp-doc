<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$allowHidden = viewer_is_admin();
$novel = $slug === '' ? null : find_novel_by_slug($slug, $allowHidden);
if ($novel === null) {
    http_response_code(404);
    $pageTitle = 'ไม่พบนิยาย';
    render_empty('fa-book', 'ไม่พบนิยายเรื่องนี้', 'เรื่องอาจถูกปิดการเผยแพร่ หรือที่อยู่ไม่ถูกต้อง');
    return;
}

$pageTitle = (string) $novel['title'];
$pageDescription = mb_substr(trim((string) $novel['synopsis']), 0, 150);
bump_view('novels', 'novels', (int) $novel['id']);
$chapters = chapters_of((int) $novel['id'], $allowHidden);
$length = 0;
foreach ($chapters as $chapter) {
    if ((int) $chapter['is_published'] === 1 || $allowHidden) {
        $length += (int) $chapter['content_length'];
    }
}
$me = current_user();
$bookmarked = $me ? is_bookmarked((int) $me['id'], (int) $novel['id']) : false;
$progress = $me ? progress_for((int) $me['id'], (int) $novel['id']) : null;
$firstNumber = isset($chapters[0]) ? $chapters[0]['chapter_number'] : null;
$hue = cover_hue((string) $novel['slug']);
?>
<article class="novel-detail">
    <div class="detail-cover" style="--hue: <?= $hue ?>">
        <?php if (!empty($novel['cover_image'])): ?>
            <img src="<?= e(upload_url((string) $novel['cover_image'])) ?>" alt="">
        <?php else: ?>
            <span class="cover-kicker"><?= e($novel['category_name']) ?></span>
            <strong><?= e($novel['title']) ?></strong>
            <em><?= e($novel['author_name']) ?></em>
        <?php endif; ?>
    </div>
    <div>
        <p class="mb-2">
            <a class="category-link" href="<?= e(url('category', ['slug' => $novel['category_slug']])) ?>"><?= e($novel['category_name']) ?></a>
            <span class="status-pill status-<?= e((string) $novel['status']) ?>"><?= e(novel_status_label((string) $novel['status'])) ?></span>
            <?php if ((int) $novel['is_published'] !== 1): ?>
                <span class="status-pill">ยังไม่เผยแพร่</span>
            <?php endif; ?>
        </p>
        <h1><?= e($novel['title']) ?></h1>
        <p class="author"><i class="fa-solid fa-feather" aria-hidden="true"></i> <?= e($novel['author_name']) ?></p>
        <p class="meta"><?= number_format(count($chapters)) ?> ตอน · <?= number_format($length) ?> ตัวอักษร · อ่าน <?= number_format((int) $novel['view_count']) ?> ครั้ง</p>
        <p class="synopsis"><?= nl2br(e((string) $novel['synopsis']), false) ?></p>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($firstNumber !== null): ?>
                <?php $start = $progress['chapter_number'] ?? $firstNumber; ?>
                <a class="btn btn-seal" href="<?= e(url('read', ['slug' => $novel['slug'], 'chapter' => (int) $start])) ?>">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                    <?= $progress ? 'อ่านต่อตอนที่ ' . number_format((int) $progress['chapter_number']) : 'เริ่มอ่าน' ?>
                </a>
            <?php endif; ?>
            <?php if ($me): ?>
                <form method="post" action="<?= e(url('bookmark')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="novel_id" value="<?= (int) $novel['id'] ?>">
                    <input type="hidden" name="back" value="<?= e(current_url()) ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fa-<?= $bookmarked ? 'solid' : 'regular' ?> fa-bookmark" aria-hidden="true"></i>
                        <?= $bookmarked ? 'เอาออกจากชั้นหนังสือ' : 'เก็บเข้าชั้นหนังสือ' ?>
                    </button>
                </form>
            <?php else: ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('login')) ?>">เข้าสู่ระบบเพื่อเก็บเรื่องนี้</a>
            <?php endif; ?>
            <?php if ($allowHidden): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('admin-novel-form', ['id' => (int) $novel['id']])) ?>">แก้ไขเรื่องนี้</a>
            <?php endif; ?>
        </div>
        <h2 class="h4 mt-4">สารบัญ</h2>
        <?php if ($chapters === []): ?>
            <?php render_empty('fa-list', 'ยังไม่มีตอน', 'ผู้ดูแลสามารถเพิ่มตอนได้จากหน้าจัดการ'); ?>
        <?php else: ?>
            <ol class="chapter-index">
                <?php foreach ($chapters as $chapter): ?>
                    <li>
                        <a href="<?= e(url('read', ['slug' => $novel['slug'], 'chapter' => (int) $chapter['chapter_number']])) ?>">
                            <span>ตอนที่ <?= number_format((int) $chapter['chapter_number']) ?> · <?= e($chapter['title']) ?></span>
                            <small><?= (int) $chapter['is_published'] === 1 ? number_format((int) $chapter['content_length']) . ' ตัวอักษร' : 'ฉบับร่าง' ?></small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</article>
