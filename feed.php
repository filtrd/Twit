<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

header('Content-Type: application/json; charset=utf-8');

$username = trim($_GET['u'] ?? '');
$profile = null;
if ($username !== '') {
    $stmt = db()->prepare('SELECT id, username FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $profile = $stmt->fetch();
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
}

$feedPageSize = (int)$feedPageSize;
$cursor = trim($_GET['cursor'] ?? '');
$cursorCreatedAt = null;
$cursorId = null;

if ($cursor !== '') {
    try {
        $decodedCursor = decodeFeedCursor($cursor);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid cursor']);
        exit;
    }
    $cursorCreatedAt = $decodedCursor['created_at'];
    $cursorId = $decodedCursor['id'];
}

$sql = <<<SQL
SELECT p.id, p.content, p.image_path, p.created_at, p.updated_at, p.edit_count, u.id AS user_id, u.username, u.avatar_path,
       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
FROM posts p
JOIN users u ON u.id = p.user_id
SQL;

$params = [];
if ($profile) {
    $sql .= ' WHERE p.user_id = ?';
    $params[] = (int)$profile['id'];
}
if ($cursorCreatedAt !== null) {
    $sql .= ($profile ? ' AND' : ' WHERE') . ' (p.created_at, p.id) < (?, ?)';
    $params[] = $cursorCreatedAt;
    $params[] = $cursorId;
}

$sql .= ' ORDER BY p.created_at DESC, p.id DESC LIMIT ' . ($feedPageSize + 1);
$stmt = db()->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$hasMore = count($posts) > $feedPageSize;
if ($hasMore) array_pop($posts);

$nextCursor = null;
if ($hasMore && $posts) $nextCursor = encodeFeedCursor($posts[array_key_last($posts)]);

$user = current_user();
$redirect = $profile ? 'profile' : 'index';
ob_start();
foreach ($posts as $post) renderPost($post, $user, $redirect);
$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'next_cursor' => $nextCursor,
    'has_more' => $hasMore,
], JSON_THROW_ON_ERROR);
