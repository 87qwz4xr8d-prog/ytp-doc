<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

/** @var array $chapters */
/** @var array $novel */
/** @var int $currentNumber */
?>
<ol class="chapter-menu">
    <?php foreach ($chapters as $item): ?>
        <?php $active = (int) $item['chapter_number'] === $currentNumber; ?>
        <li>
            <a class="<?= $active ? 'is-current' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> href="<?= e(url('read', ['slug' => $novel['slug'], 'chapter' => (int) $item['chapter_number']])) ?>">
                <span>ตอนที่ <?= number_format((int) $item['chapter_number']) ?></span>
                <strong><?= e($item['title']) ?></strong>
            </a>
        </li>
    <?php endforeach; ?>
</ol>
