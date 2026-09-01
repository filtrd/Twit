<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
verify_csrf();
$content = trim($_POST['content'] ?? '');
if ($content !== '' && mb_strlen($content) <= 280) {
    $stmt = db()->prepare('INSERT INTO posts (user_id, content) VALUES (?, ?)');
    $stmt->execute([$user['id'], $content]);
}
header('Location: index.php');
