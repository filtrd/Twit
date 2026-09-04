<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

header('Content-Type: application/json; charset=utf-8');

$username = trim($_GET['u'] ?? '');
if ($username === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing profile']);
    exit;
}

$stmt = db()->prepare('SELECT id, username FROM users WHERE username = ?');
$stmt->execute([$username]);
$profile = $stmt->fetch();
if (!$profile) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$feedPageSize = (int)$feedPageSize;
$cursor = trim($_GET['cursor'] ?? '');
$cursorCreatedAt = null;
$cursorId = null;

if ($cursor !== '') {
    $encodedCursor = strtr($cursor, '-_', '+/');
    $encodedCursor .= str_repeat('=', (4 - strlen($encodedCursor) % 4) % 4);
    $decoded = base64_decode($encodedCursor, true);

    if ($decoded === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid cursor']);
        exit;
    }

    $data = json_decode($decoded, true);
    if (!is_array($data) || !isset($data['created_at'], $data['id']) || !is_string($data['created_at']) || !is_int($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid cursor']);
        exit;
    }

    $cursorCreatedAt = $data['created_at'];
    $cursorId = $data['id'];
}

$sql = <<<SQL
SELECT p.id, p.content, p.image_path, p.created_at, p.updated_at, p.edit_count, u.id AS user_id, u.username, u.avatar_path,
       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
FROM posts p
JOIN users u ON u.id = p.user_id
WHERE p.user_id = ?
SQL;

$params = [(int)$profile['id']];
if ($cursorCreatedAt !== null) {
    $sql .= ' AND (p.created_at, p.id) < (?, ?)';
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
if ($hasMore && $posts) {
    $last = $posts[array_key_last($posts)];
    $payload = json_encode([
        'created_at' => $last['created_at'],
        'id' => (int)$last['id'],
    ], JSON_THROW_ON_ERROR);
    $nextCursor = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
}

$user = current_user();
ob_start();
foreach ($posts as $post) {
    renderPost($post, $user, 'profile');
}
$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'next_cursor' => $nextCursor,
    'has_more' => $hasMore,
], JSON_THROW_ON_ERROR);
