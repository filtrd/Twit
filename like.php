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

if ($postId > 0) {
    $stmt = db()->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
    $stmt->execute([$user['id'], $postId]);

    if ($stmt->fetchColumn()) {
        $stmt = db()->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
    } else {
        $stmt = db()->prepare('INSERT OR IGNORE INTO likes (user_id, post_id) VALUES (?, ?)');
    }

    $stmt->execute([$user['id'], $postId]);
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
