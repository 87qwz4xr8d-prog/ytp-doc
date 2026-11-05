<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

/** @var array $novel */
$hue = cover_hue((string) $novel['slug']);
?>
<article class="novel-card">
    <a class="novel-cover" style="--hue: <?= $hue ?>" href="<?= e(url('novel', ['slug' => $novel['slug']])) ?>" aria-label="<?= e($novel['title']) ?>">
        <?php if (!empty($novel['cover_image'])): ?>
            <img src="<?= e(upload_url((string) $novel['cover_image'])) ?>" alt="">
        <?php else: ?>
            <span class="cover-kicker"><?= e($novel['category_name'] ?? 'นิยาย') ?></span>
            <strong><?= e($novel['title']) ?></strong>
            <em><?= e($novel['author_name']) ?></em>
        <?php endif; ?>
    </a>
    <div class="novel-card-body">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
            <a class="category-link" href="<?= e(url('category', ['slug' => $novel['category_slug']])) ?>"><?= e($novel['category_name']) ?></a>
            <span class="status-pill status-<?= e((string) $novel['status']) ?>"><?= e(novel_status_label((string) $novel['status'])) ?></span>
        </div>
        <h3><a href="<?= e(url('novel', ['slug' => $novel['slug']])) ?>"><?= e($novel['title']) ?></a></h3>
        <p class="author"><i class="fa-solid fa-feather" aria-hidden="true"></i> <?= e($novel['author_name']) ?></p>
        <p class="meta mb-0"><?= number_format((int) $novel['chapter_count']) ?> ตอน · อ่าน <?= number_format((int) $novel['view_count']) ?> ครั้ง</p>
    </div>
</article>
