<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verify_csrf();
$content = trim($_POST['content'] ?? '');
$imagePath = null;

if (!empty($_FILES['image']['name'])) {
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        header('Location: index.php');
        exit;
    }

    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        header('Location: index.php');
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['image']['tmp_name']);

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        header('Location: index.php');
        exit;
    }

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $filename)) {
        header('Location: index.php');
        exit;
    }

    $imagePath = 'uploads/' . $filename;
}

if (($content !== '' && mb_strlen($content) <= 280) || $imagePath !== null) {
    $stmt = db()->prepare('INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)');
    $stmt->execute([$user['id'], $content, $imagePath]);
}

header('Location: index.php');
