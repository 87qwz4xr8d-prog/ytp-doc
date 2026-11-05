<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('ห้ามเปิดไฟล์นี้โดยตรง');
}

function novel_select_sql(): string
{
    return 'SELECT n.id, n.title, n.slug, n.author_name, n.category_id, n.synopsis, n.cover_image,
                   n.status, n.is_published, n.view_count, n.created_by, n.created_at, n.updated_at,
                   c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
                   (SELECT COUNT(*) FROM chapters ch WHERE ch.novel_id = n.id AND ch.is_published = 1) AS chapter_count,
                   (SELECT COUNT(*) FROM chapters ch2 WHERE ch2.novel_id = n.id) AS chapter_total
            FROM novels n
            INNER JOIN categories c ON c.id = n.category_id';
}

function novel_from_sql(): string
{
    return 'FROM novels n INNER JOIN categories c ON c.id = n.category_id';
}

function novel_filters(array $filters): array
{
    $where = [];
    $params = [];

    if (empty($filters['include_unpublished'])) {
        $where[] = 'n.is_published = 1';
    }
    if (!empty($filters['category'])) {
        $where[] = 'c.slug = ?';
        $params[] = (string) $filters['category'];
    }
    if (!empty($filters['status']) && in_array($filters['status'], ['ongoing', 'completed', 'hiatus'], true)) {
        $where[] = 'n.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['slug'])) {
        $where[] = 'n.slug = ?';
        $params[] = (string) $filters['slug'];
    }
    if (!empty($filters['id'])) {
        $where[] = 'n.id = ?';
        $params[] = (int) $filters['id'];
    }
    if (!empty($filters['q'])) {
        $like = like_pattern(mb_substr((string) $filters['q'], 0, 100));
        $where[] = "(n.title LIKE ? ESCAPE '\\\\' OR n.author_name LIKE ? ESCAPE '\\\\' OR n.synopsis LIKE ? ESCAPE '\\\\' OR c.name LIKE ? ESCAPE '\\\\')";
        array_push($params, $like, $like, $like, $like);
    }
    if ($where === []) {
        $where[] = '1 = 1';
    }

    $order = match ($filters['sort'] ?? 'new') {
        'popular' => 'n.view_count DESC, n.updated_at DESC',
        'title' => 'n.title ASC',
        'old' => 'n.created_at ASC',
        default => 'n.updated_at DESC',
    };

    return [$where, $params, $order];
}

function query_novels(array $filters, int $limit, int $offset = 0): array
{
    [$where, $params, $order] = novel_filters($filters);
    $limit = max(1, min(60, $limit));
    $offset = max(0, $offset);
    $sql = novel_select_sql()
        . ' WHERE ' . implode(' AND ', $where)
        . ' ORDER BY ' . $order
        . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function count_novels(array $filters): int
{
    [$where, $params] = novel_filters($filters);
    $sql = 'SELECT COUNT(*) ' . novel_from_sql() . ' WHERE ' . implode(' AND ', $where);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function list_novels(array $filters): array
{
    $perPage = (int) ($filters['per_page'] ?? app_config()['app']['per_page'] ?? 9);
    $perPage = max(1, min(60, $perPage));
    $total = count_novels($filters);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min(10000, (int) ($filters['page'] ?? 1)));
    if ($page > $pages) {
        $page = $pages;
    }
    $rows = query_novels($filters, $perPage, ($page - 1) * $perPage);

    return [
        'rows' => $rows,
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'per_page' => $perPage,
    ];
}

function find_novel_by_slug(string $slug, bool $includeUnpublished = false): ?array
{
    $rows = query_novels([
        'slug' => $slug,
        'include_unpublished' => $includeUnpublished,
    ], 1);

    return $rows[0] ?? null;
}

function find_novel(int $id, bool $includeUnpublished = true): ?array
{
    if ($id <= 0) {
        return null;
    }
    $rows = query_novels([
        'id' => $id,
        'include_unpublished' => $includeUnpublished,
    ], 1);

    return $rows[0] ?? null;
}

function categories_with_counts(): array
{
    $sql = 'SELECT c.*, COUNT(n.id) AS novel_count
            FROM categories c
            LEFT JOIN novels n ON n.category_id = c.id AND n.is_published = 1
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC';

    return db()->query($sql)->fetchAll();
}

function all_categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
}

function find_category(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function find_category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function latest_novels(int $limit = 8): array
{
    return query_novels(['sort' => 'new'], $limit);
}

function popular_novels(int $limit = 4): array
{
    return query_novels(['sort' => 'popular'], $limit);
}

function catalog_counts(): array
{
    return [
        'novels' => (int) db()->query('SELECT COUNT(*) FROM novels WHERE is_published = 1')->fetchColumn(),
        'chapters' => (int) db()->query(
            'SELECT COUNT(*) FROM chapters ch
             INNER JOIN novels n ON n.id = ch.novel_id
             WHERE ch.is_published = 1 AND n.is_published = 1'
        )->fetchColumn(),
        'categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    ];
}

function chapters_of(int $novelId, bool $includeUnpublished = false): array
{
    $sql = 'SELECT id, novel_id, chapter_number, title, is_published, view_count, created_at, updated_at,
                   CHAR_LENGTH(content) AS content_length
            FROM chapters
            WHERE novel_id = ?';
    if (!$includeUnpublished) {
        $sql .= ' AND is_published = 1';
    }
    $sql .= ' ORDER BY chapter_number ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$novelId]);

    return $stmt->fetchAll();
}

function find_chapter_by_number(int $novelId, int $number, bool $includeUnpublished = false): ?array
{
    $sql = 'SELECT * FROM chapters WHERE novel_id = ? AND chapter_number = ?';
    if (!$includeUnpublished) {
        $sql .= ' AND is_published = 1';
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$novelId, $number]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function find_chapter(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM chapters WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function adjacent_from(array $chapters, int $number): array
{
    $previous = null;
    $next = null;
    foreach ($chapters as $chapter) {
        $chapterNumber = (int) $chapter['chapter_number'];
        if ($chapterNumber < $number) {
            $previous = $chapter;
        } elseif ($chapterNumber > $number) {
            $next = $chapter;
            break;
        }
    }

    return ['prev' => $previous, 'next' => $next];
}

function next_chapter_number(int $novelId): int
{
    $stmt = db()->prepare('SELECT COALESCE(MAX(chapter_number), 0) + 1 FROM chapters WHERE novel_id = ?');
    $stmt->execute([$novelId]);

    return (int) $stmt->fetchColumn();
}

function bump_view(string $bucket, string $table, int $id): void
{
    $allowed = [
        'novels' => 'novels',
        'chapters' => 'chapters',
    ];
    if ($id <= 0 || !isset($allowed[$table])) {
        return;
    }
    if (!isset($_SESSION['viewed']) || !is_array($_SESSION['viewed'])) {
        $_SESSION['viewed'] = [];
    }
    if (!isset($_SESSION['viewed'][$bucket]) || !is_array($_SESSION['viewed'][$bucket])) {
        $_SESSION['viewed'][$bucket] = [];
    }
    if (!empty($_SESSION['viewed'][$bucket][$id])) {
        return;
    }

    $_SESSION['viewed'][$bucket][$id] = 1;
    // คงเวลาแก้ไขเดิมไว้ การเปิดอ่านไม่ควรดันเรื่องขึ้นไปอยู่ในรายการอัปเดต
    $stmt = db()->prepare(
        'UPDATE ' . $allowed[$table] . ' SET view_count = view_count + 1, updated_at = updated_at WHERE id = ?'
    );
    $stmt->execute([$id]);
}

function save_progress(int $userId, int $novelId, int $chapterId): void
{
    $sql = 'INSERT INTO reading_progress (user_id, novel_id, chapter_id, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE chapter_id = ?, updated_at = NOW()';
    db()->prepare($sql)->execute([$userId, $novelId, $chapterId, $chapterId]);
}

function progress_for(int $userId, int $novelId): ?array
{
    $sql = 'SELECT ch.id, ch.chapter_number, ch.title, rp.updated_at
            FROM reading_progress rp
            INNER JOIN chapters ch ON ch.id = rp.chapter_id
            WHERE rp.user_id = ? AND rp.novel_id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId, $novelId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function reading_history(int $userId, ?int $limit = null): array
{
    $sql = 'SELECT n.id, n.title, n.slug, n.author_name, n.cover_image, n.status,
                   c.name AS category_name, c.slug AS category_slug,
                   ch.chapter_number, ch.title AS chapter_title, rp.updated_at
            FROM reading_progress rp
            INNER JOIN novels n ON n.id = rp.novel_id AND n.is_published = 1
            INNER JOIN chapters ch ON ch.id = rp.chapter_id AND ch.is_published = 1
            INNER JOIN categories c ON c.id = n.category_id
            WHERE rp.user_id = ?
            ORDER BY rp.updated_at DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, min(50, $limit));
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function is_bookmarked(int $userId, int $novelId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND novel_id = ?');
    $stmt->execute([$userId, $novelId]);

    return (bool) $stmt->fetchColumn();
}

function toggle_bookmark(int $userId, int $novelId): bool
{
    if (is_bookmarked($userId, $novelId)) {
        db()->prepare('DELETE FROM bookmarks WHERE user_id = ? AND novel_id = ?')->execute([$userId, $novelId]);

        return false;
    }

    try {
        db()->prepare('INSERT INTO bookmarks (user_id, novel_id) VALUES (?, ?)')->execute([$userId, $novelId]);
    } catch (PDOException $exception) {
        $code = (int) ($exception->errorInfo[1] ?? 0);
        if ($code !== 1062) {
            throw $exception;
        }
    }

    return true;
}

function bookmarks_of(int $userId): array
{
    $sql = novel_select_sql()
        . ' INNER JOIN bookmarks b ON b.novel_id = n.id AND b.user_id = ?
            WHERE n.is_published = 1
            ORDER BY b.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function dashboard_stats(): array
{
    $queries = [
        'novels' => 'SELECT COUNT(*) FROM novels',
        'published' => 'SELECT COUNT(*) FROM novels WHERE is_published = 1',
        'chapters' => 'SELECT COUNT(*) FROM chapters',
        'users' => 'SELECT COUNT(*) FROM users',
        'views' => 'SELECT COALESCE(SUM(view_count), 0) FROM novels',
        'bookmarks' => 'SELECT COUNT(*) FROM bookmarks',
    ];
    $stats = [];
    foreach ($queries as $key => $sql) {
        $stats[$key] = (int) db()->query($sql)->fetchColumn();
    }

    return $stats;
}

function recent_chapters(int $limit = 6): array
{
    $limit = max(1, min(20, $limit));
    $sql = 'SELECT ch.id, ch.title, ch.chapter_number, ch.is_published, ch.updated_at,
                   n.id AS novel_id, n.title AS novel_title, n.slug AS novel_slug
            FROM chapters ch
            INNER JOIN novels n ON n.id = ch.novel_id
            ORDER BY ch.updated_at DESC
            LIMIT ' . $limit;

    return db()->query($sql)->fetchAll();
}

function all_users(): array
{
    return db()->query(
        "SELECT id, username, email, display_name, role, created_at
         FROM users
         ORDER BY FIELD(role, 'admin', 'member'), id ASC"
    )->fetchAll();
}

function find_user(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, username, email, display_name, role, created_at FROM users WHERE id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function unique_slug(string $table, string $slug, ?int $ignoreId = null): string
{
    if (!in_array($table, ['novels', 'categories'], true)) {
        throw new InvalidArgumentException('ตารางไม่ถูกต้อง');
    }

    $slug = $slug !== '' ? $slug : 'item';
    $candidate = $slug;
    $suffix = 2;
    while ($suffix < 300) {
        if ($ignoreId !== null) {
            $stmt = db()->prepare('SELECT id FROM ' . $table . ' WHERE slug = ? AND id <> ?');
            $stmt->execute([$candidate, $ignoreId]);
        } else {
            $stmt = db()->prepare('SELECT id FROM ' . $table . ' WHERE slug = ?');
            $stmt->execute([$candidate]);
        }
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $suffix;
        $suffix++;
    }

    return $slug . '-' . bin2hex(random_bytes(3));
}

function touch_novel(int $novelId): void
{
    db()->prepare('UPDATE novels SET updated_at = NOW() WHERE id = ?')->execute([$novelId]);
}

function viewer_is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}
