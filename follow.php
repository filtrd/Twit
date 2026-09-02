<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verify_csrf();
$followingId = (int)($_POST['user_id'] ?? 0);

if ($followingId > 0 && $followingId !== (int)$user['id']) {
    $stmt = db()->prepare('SELECT 1 FROM users WHERE id = ?');
    $stmt->execute([$followingId]);

    if ($stmt->fetchColumn()) {
        $stmt = db()->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
        $stmt->execute([$user['id'], $followingId]);

        if ($stmt->fetchColumn()) {
            $stmt = db()->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
        } else {
            $stmt = db()->prepare('INSERT OR IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)');
        }

        $stmt->execute([$user['id'], $followingId]);
    }
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
