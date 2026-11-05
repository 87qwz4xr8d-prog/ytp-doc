<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data:; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
        . "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:; "
        . "script-src 'self' https://cdn.jsdelivr.net; "
        . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
    );
}

function e(mixed $value): string
{
    if (is_int($value) || is_float($value)) {
        $value = (string) $value;
    } elseif (is_bool($value)) {
        $value = $value ? '1' : '0';
    } elseif (!is_string($value)) {
        $value = '';
    }

    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $page, array $params = []): string
{
    $query = array_merge(['page' => $page], $params);
    $query = array_filter(
        $query,
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );

    return 'index.php?' . http_build_query($query);
}

function asset(string $path): string
{
    return app_base_path() . '/assets/' . ltrim($path, '/');
}

function upload_url(string $name): string
{
    return app_base_path() . '/uploads/covers/' . rawurlencode(basename($name));
}

function redirect(string $to): never
{
    $to = str_replace(["\r", "\n"], '', $to);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . $to);
    exit;
}

function current_url(): string
{
    $query = $_GET;
    if (!is_array($query)) {
        $query = [];
    }

    return 'index.php' . ($query === [] ? '' : '?' . http_build_query($query));
}

function is_internal_app_url(string $target): bool
{
    if ($target === '' || preg_match('#^(?:https?:)?//#i', $target)) {
        return false;
    }
    if (str_contains($target, "\n") || str_contains($target, "\r")) {
        return false;
    }

    $path = parse_url($target, PHP_URL_PATH);

    return $path === 'index.php' || $path === 'install.php';
}

function request_target_or_home(): string
{
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $parts = parse_url($ref);
    if (!is_array($parts)) {
        return url('home');
    }

    $path = (string) ($parts['path'] ?? '');
    $query = str_replace(["\r", "\n"], '', (string) ($parts['query'] ?? ''));
    if (str_ends_with($path, '/install.php')) {
        return 'install.php';
    }
    if (str_ends_with($path, '/index.php') || $path === 'index.php') {
        return 'index.php' . ($query !== '' ? '?' . $query : '');
    }

    return url('home');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (is_string($sent) && hash_equals(csrf_token(), $sent)) {
        return;
    }

    flash('error', 'คำขอหมดอายุหรือไม่ถูกต้อง กรุณาลองใหม่');
    redirect(request_target_or_home());
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => normalize_flash_type($type),
        'message' => $message,
    ];
}

function normalize_flash_type(string $type): string
{
    return in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
}

function pull_flash(): ?array
{
    static $loaded = false;
    static $flash = null;

    if ($loaded) {
        return $flash;
    }

    $loaded = true;
    $stored = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (!is_array($stored) || !is_string($stored['message'] ?? null) || $stored['message'] === '') {
        return null;
    }

    $flash = [
        'type' => normalize_flash_type((string) ($stored['type'] ?? 'info')),
        'message' => $stored['message'],
    ];

    return $flash;
}

function flash_title(string $type): string
{
    return match ($type) {
        'success' => 'เรียบร้อย',
        'error' => 'ไม่สำเร็จ',
        'warning' => 'โปรดทราบ',
        default => 'แจ้งให้ทราบ',
    };
}

function flash_bootstrap(string $type): string
{
    return match ($type) {
        'success' => 'success',
        'error' => 'danger',
        'warning' => 'warning',
        default => 'info',
    };
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function old(string $key, mixed $default = ''): mixed
{
    $old = $_SESSION['old'] ?? null;
    if (!is_array($old) || !array_key_exists($key, $old)) {
        return $default;
    }

    return $old[$key];
}

function remember_old(array $input): void
{
    unset($input['csrf'], $input['password'], $input['password_confirm'], $input['current_password']);
    $_SESSION['old'] = $input;
}

function input(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

function input_int(string $key): int
{
    $value = $_POST[$key] ?? 0;
    if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
        return (int) $value;
    }
    if (is_int($value)) {
        return $value;
    }

    return 0;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = app_config()['db'] ?? [];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        (string) ($c['host'] ?? '127.0.0.1'),
        (int) ($c['port'] ?? 3306),
        (string) ($c['name'] ?? ''),
        (string) ($c['charset'] ?? 'utf8mb4')
    );

    $pdo = new PDO($dsn, (string) ($c['user'] ?? ''), (string) ($c['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+07:00'");

    return $pdo;
}

function current_user(): ?array
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) {
        return null;
    }

    static $user = null;
    static $loadedFor = null;
    $userId = (int) $userId;
    if ($loadedFor === $userId) {
        return $user;
    }

    try {
        $stmt = db()->prepare('SELECT id, username, email, display_name, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch() ?: null;
    } catch (Throwable) {
        return null;
    }

    $loadedFor = $userId;
    if ($user === null) {
        unset($_SESSION['user_id']);
    }

    return $user;
}

function require_login(): void
{
    if (current_user() !== null) {
        return;
    }

    flash('warning', 'กรุณาเข้าสู่ระบบก่อนใช้งานส่วนนี้');
    $_SESSION['redirect_after_login'] = current_url();
    redirect(url('login'));
}

function require_admin(): void
{
    require_login();
    if ((current_user()['role'] ?? '') === 'admin') {
        return;
    }

    flash('error', 'ส่วนนี้สำหรับผู้ดูแลระบบ');
    redirect(url('home'));
}

function slugify(string $text): string
{
    $text = trim(mb_strtolower($text));
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? '';
    $text = trim((string) $text, '-');
    if ($text === '') {
        $text = 'item';
    }

    return mb_substr($text, 0, 180);
}

function like_pattern(string $value): string
{
    $value = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);

    return '%' . $value . '%';
}

function novel_status_label(string $status): string
{
    return match ($status) {
        'ongoing' => 'กำลังเขียน',
        'completed' => 'จบแล้ว',
        'hiatus' => 'พักเรื่อง',
        default => $status,
    };
}

function icon_class(string $icon): string
{
    $icon = trim($icon);
    if (!preg_match('/^fa-[a-z0-9-]+$/', $icon)) {
        $icon = 'fa-book';
    }

    return 'fa-solid ' . $icon;
}

function cover_hue(string $seed): int
{
    $unsigned = (int) sprintf('%u', crc32($seed));

    return $unsigned % 360;
}

function thai_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }

    $months = [
        1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.',
    ];

    return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)]
        . ' ' . ((int) date('Y', $timestamp) + 543)
        . ' ' . date('H:i', $timestamp);
}

function render_chapter_html(string $content): string
{
    $content = str_replace(["\r\n", "\r"], "\n", trim($content));
    if ($content === '') {
        return '';
    }

    $blocks = preg_split("/\n{2,}/u", $content) ?: [];
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $html .= '<p>' . nl2br(e($block), false) . '</p>';
    }

    return $html;
}

function render_empty(string $icon, string $title, string $text): void
{
    if (!preg_match('/^fa-[a-z0-9-]+$/', $icon)) {
        $icon = 'fa-book';
    }

    echo '<div class="empty-state">';
    echo '<i class="fa-solid ' . e($icon) . '" aria-hidden="true"></i>';
    echo '<h2>' . e($title) . '</h2>';
    echo '<p>' . e($text) . '</p>';
    echo '</div>';
}

function render_pagination(int $current, int $pages, array $baseQuery): void
{
    if ($pages <= 1) {
        return;
    }

    $link = static function (int $page) use ($baseQuery): string {
        $baseQuery['p'] = $page;

        return 'index.php?' . http_build_query($baseQuery);
    };

    echo '<nav class="novel-pagination" aria-label="การแบ่งหน้า"><ul class="pagination pagination-sm flex-wrap justify-content-center">';
    $prevDisabled = $current <= 1 ? ' disabled' : '';
    echo '<li class="page-item' . $prevDisabled . '">';
    if ($current <= 1) {
        echo '<span class="page-link">ก่อนหน้า</span>';
    } else {
        echo '<a class="page-link" href="' . e($link($current - 1)) . '">ก่อนหน้า</a>';
    }
    echo '</li>';

    $windowStart = max(1, $current - 2);
    $windowEnd = min($pages, $current + 2);
    $sequence = [1];
    if ($windowStart > 2) {
        $sequence[] = 0;
    }
    for ($i = max(2, $windowStart); $i <= min($pages - 1, $windowEnd); $i++) {
        $sequence[] = $i;
    }
    if ($windowEnd < $pages - 1) {
        $sequence[] = 0;
    }
    if ($pages > 1) {
        $sequence[] = $pages;
    }

    $seen = [];
    foreach ($sequence as $item) {
        $key = (string) $item . ':' . count($seen);
        if ($item !== 0 && isset($seen[$item])) {
            continue;
        }
        if ($item !== 0) {
            $seen[$item] = true;
        }
        unset($key);
        if ($item === 0) {
            echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            continue;
        }
        $active = $item === $current ? ' active' : '';
        $currentAttr = $item === $current ? ' aria-current="page"' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . e($link($item)) . '"' . $currentAttr . '>' . $item . '</a></li>';
    }

    $nextDisabled = $current >= $pages ? ' disabled' : '';
    echo '<li class="page-item' . $nextDisabled . '">';
    if ($current >= $pages) {
        echo '<span class="page-link">ถัดไป</span>';
    } else {
        echo '<a class="page-link" href="' . e($link($current + 1)) . '">ถัดไป</a>';
    }
    echo '</li></ul></nav>';
}

function render_flash_fallback(): void
{
    $flash = pull_flash();
    if ($flash === null) {
        return;
    }

    $type = flash_bootstrap($flash['type']);
    echo '<div class="container-xxl pt-3" id="flash-fallback">';
    echo '<div class="alert alert-' . e($type) . ' mb-0" role="status">' . nl2br(e($flash['message']), false) . '</div>';
    echo '</div>';
}

function cover_path(?string $name): ?string
{
    if ($name === null || $name === '') {
        return null;
    }

    return APP_ROOT . '/uploads/covers/' . basename($name);
}

function delete_cover(?string $name): void
{
    $path = cover_path($name);
    if ($path !== null && is_file($path)) {
        unlink($path);
    }
}

function store_cover(array $file): array
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['name' => null, 'error' => null];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['name' => null, 'error' => 'อัปโหลดปกไม่สำเร็จ'];
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['name' => null, 'error' => 'ขนาดปกต้องไม่เกิน 2 MB'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['name' => null, 'error' => 'ไฟล์ปกไม่ถูกต้อง'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime]) || @getimagesize($tmp) === false) {
        return ['name' => null, 'error' => 'ปกต้องเป็นรูป JPG, PNG หรือ WEBP'];
    }

    $directory = APP_ROOT . '/uploads/covers';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        return ['name' => null, 'error' => 'สร้างโฟลเดอร์สำหรับปกไม่ได้'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($tmp, $directory . '/' . $filename)) {
        return ['name' => null, 'error' => 'บันทึกไฟล์ปกไม่ได้'];
    }

    return ['name' => $filename, 'error' => null];
}
