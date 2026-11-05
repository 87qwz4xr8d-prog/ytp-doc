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
    $pageTitle = 'ไม่พบตอน';
    render_empty('fa-book', 'ไม่พบนิยายเรื่องนี้', 'กลับไปที่ชั้นหนังสือแล้วเลือกเรื่องอื่น');
    return;
}

$chapters = chapters_of((int) $novel['id'], $allowHidden);
$requested = (int) ($_GET['chapter'] ?? 0);
if ($requested <= 0) {
    $target = 0;
    $me = current_user();
    if ($me) {
        $progress = progress_for((int) $me['id'], (int) $novel['id']);
        if ($progress) {
            $target = (int) $progress['chapter_number'];
        }
    }
    if ($target <= 0 && isset($chapters[0])) {
        $target = (int) $chapters[0]['chapter_number'];
    }
    if ($target > 0) {
        redirect(url('read', ['slug' => $novel['slug'], 'chapter' => $target]));
    }
    $pageTitle = (string) $novel['title'];
    render_empty('fa-scroll', 'เรื่องนี้ยังไม่มีตอนให้อ่าน', 'กลับไปที่หน้ารายละเอียดเพื่อดูสถานะของเรื่อง');
    echo '<p class="text-center"><a class="btn btn-seal" href="' . e(url('novel', ['slug' => $novel['slug']])) . '">ไปหน้ารายละเอียด</a></p>';
    return;
}

$chapter = find_chapter_by_number((int) $novel['id'], $requested, $allowHidden);
if ($chapter === null) {
    http_response_code(404);
    $pageTitle = 'ไม่พบตอน';
    render_empty('fa-scroll', 'ไม่พบตอนนี้', 'ตอนอาจยังไม่เผยแพร่ หรือเลขตอนไม่ถูกต้อง');
    echo '<p class="text-center"><a class="btn btn-seal" href="' . e(url('novel', ['slug' => $novel['slug']])) . '">ดูสารบัญ</a></p>';
    return;
}

$pageTitle = $chapter['title'] . ' — ' . $novel['title'];
$pageDescription = 'ตอนที่ ' . $chapter['chapter_number'] . ' ของ ' . $novel['title'];
bump_view('novels', 'novels', (int) $novel['id']);
bump_view('chapters', 'chapters', (int) $chapter['id']);
$me = current_user();
if ($me && (int) $chapter['is_published'] === 1 && (int) $novel['is_published'] === 1) {
    save_progress((int) $me['id'], (int) $novel['id'], (int) $chapter['id']);
}
$neighbors = adjacent_from($chapters, (int) $chapter['chapter_number']);
$prevUrl = $neighbors['prev'] ? url('read', ['slug' => $novel['slug'], 'chapter' => (int) $neighbors['prev']['chapter_number']]) : '';
$nextUrl = $neighbors['next'] ? url('read', ['slug' => $novel['slug'], 'chapter' => (int) $neighbors['next']['chapter_number']]) : '';
$currentNumber = (int) $chapter['chapter_number'];
?>
<article class="reader" id="reader" data-prev="<?= e($prevUrl) ?>" data-next="<?= e($nextUrl) ?>">
    <p class="mb-1"><a href="<?= e(url('novel', ['slug' => $novel['slug']])) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= e($novel['title']) ?></a></p>
    <p class="eyebrow mb-0">ตอนที่ <?= number_format($currentNumber) ?> · <?= e($novel['author_name']) ?></p>
    <h1><?= e($chapter['title']) ?></h1>
    <div class="reader-toolbar" role="toolbar" aria-label="เครื่องมือการอ่าน">
        <button class="btn btn-outline-secondary btn-sm" type="button" data-font="-1" aria-label="ลดขนาดตัวอักษร"><i class="fa-solid fa-minus"></i></button>
        <span id="fontSizeLabel" aria-live="polite">19</span>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-font="1" aria-label="เพิ่มขนาดตัวอักษร"><i class="fa-solid fa-plus"></i></button>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-font="reset">ขนาดเดิม</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-theme-toggle>โหมดอ่าน</button>
        <button class="btn btn-outline-secondary btn-sm d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#chapterDrawer">
            <i class="fa-solid fa-list" aria-hidden="true"></i> สารบัญ
        </button>
    </div>
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="reader-body" id="reader-body">
                <?= render_chapter_html((string) $chapter['content']) ?>
            </div>
            <nav class="reader-turn" aria-label="ตอนก่อนหน้าและตอนถัดไป">
                <?php if ($neighbors['prev']): ?>
                    <a href="<?= e($prevUrl) ?>"><small>ตอนก่อนหน้า</small><br><strong><?= e($neighbors['prev']['title']) ?></strong></a>
                <?php else: ?>
                    <span><small>ตอนก่อนหน้า</small><br><strong>นี่คือตอนแรก</strong></span>
                <?php endif; ?>
                <?php if ($neighbors['next']): ?>
                    <a href="<?= e($nextUrl) ?>"><small>ตอนถัดไป</small><br><strong><?= e($neighbors['next']['title']) ?></strong></a>
                <?php else: ?>
                    <span><small>ตอนถัดไป</small><br><strong>ยังไม่มีตอนใหม่</strong></span>
                <?php endif; ?>
            </nav>
        </div>
        <div class="col-xl-4 d-none d-xl-block">
            <aside class="chapter-panel" aria-label="สารบัญตอน">
                <h2 class="h6">สารบัญ</h2>
                <?php include APP_ROOT . '/includes/partials/chapter-list.php'; ?>
            </aside>
        </div>
    </div>
</article>
<div class="offcanvas offcanvas-end reader-drawer" tabindex="-1" id="chapterDrawer" aria-labelledby="chapterDrawerLabel">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="chapterDrawerLabel">สารบัญ</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="ปิด"></button>
    </div>
    <div class="offcanvas-body">
        <?php include APP_ROOT . '/includes/partials/chapter-list.php'; ?>
    </div>
</div>
