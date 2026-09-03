<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/database.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verify_csrf();

$postId = (int)($_POST['post_id'] ?? 0);
$parentId = (int)($_POST['parent_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$redirect = ($_POST['redirect'] ?? '') === 'profile' ? 'profile' : 'index';

if ($postId <= 0 || $content === '') {
    set_flash('comment_error', 'Comment cannot be empty.');
    header('Location: ' . ($redirect === 'profile' ? 'profile.php?u=' . urlencode($user['username']) : 'index.php'));
    exit;
}

if (postCharacterCount($content) > (int)$maxPostLength) {
    set_flash('comment_error', 'Comment is too long. Maximum length is ' . (int)$maxPostLength . ' characters.');
    header('Location: ' . ($redirect === 'profile' ? 'profile.php?u=' . urlencode($user['username']) : 'index.php') . '#post-' . $postId);
    exit;
}

$stmt = db()->prepare('SELECT id FROM posts WHERE id = ?');
$stmt->execute([$postId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit('Post not found');
}

if ($parentId > 0) {
    $stmt = db()->prepare('SELECT id FROM comments WHERE id = ? AND post_id = ?');
    $stmt->execute([$parentId, $postId]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        exit('Invalid reply');
    }
} else {
    $parentId = null;
}

$stmt = db()->prepare('INSERT INTO comments (post_id, user_id, parent_id, content) VALUES (?, ?, ?, ?)');
$stmt->execute([$postId, $user['id'], $parentId, $content]);

$target = $redirect === 'profile'
    ? 'profile.php?u=' . urlencode($user['username'])
    : 'index.php';

header('Location: ' . $target . '#post-' . $postId);
exit;
