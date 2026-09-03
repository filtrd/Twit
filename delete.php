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

$stmt = db()->prepare(
    'SELECT p.image_path, u.username
     FROM posts p
     JOIN users u ON u.id = p.user_id
     WHERE p.id = ? AND p.user_id = ?'
);
$stmt->execute([$postId, $user['id']]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit;
}

if (!empty($post['image_path'])) {
    @unlink(__DIR__ . '/' . $post['image_path']);
}

$stmt = db()->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
$stmt->execute([$postId, $user['id']]);

if (($_POST['redirect'] ?? '') === 'profile') {
    header('Location: profile.php?u=' . urlencode($post['username']));
} else {
    header('Location: index.php');
}
exit;
