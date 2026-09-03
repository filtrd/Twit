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
    $uploadError = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($uploadError !== UPLOAD_ERR_OK) {
        $message = match ($uploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image is too large. Maximum size is 5 MB.',
            UPLOAD_ERR_PARTIAL => 'Image upload was incomplete. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Image upload failed. Please try again later.',
            UPLOAD_ERR_CANT_WRITE => 'Image could not be saved. Please try again later.',
            UPLOAD_ERR_EXTENSION => 'Image upload was blocked by the server.',
            default => 'Image upload failed. Please try again.',
        };

        set_flash('post_error', $message);
        header('Location: index.php');
        exit;
    }

    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        set_flash('post_error', 'Image is too large. Maximum size is 5 MB.');
        header('Location: index.php');
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['image']['tmp_name']);

    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if (!in_array($mime, $allowedMimes, true)) {
        set_flash('post_error', 'Please upload a JPEG, PNG, or WebP image.');
        header('Location: index.php');
        exit;
    }

    if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
        set_flash('post_error', 'Image processing is unavailable. Please try again later.');
        header('Location: index.php');
        exit;
    }

    $image = imagecreatefromstring(
        file_get_contents($_FILES['image']['tmp_name'])
    );

    if ($image === false) {
        set_flash('post_error', "We couldn't process that image. Please try another one.");
        header('Location: index.php');
        exit;
    }

    imagepalettetotruecolor($image);
    imagealphablending($image, false);
    imagesavealpha($image, true);

    $uploadDir = __DIR__ . '/uploads/posts';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        imagedestroy($image);
        set_flash('post_error', 'Image could not be saved. Please try again later.');
        header('Location: index.php');
        exit;
    }

    $filename = bin2hex(random_bytes(16)) . '.webp';
    $destination = $uploadDir . '/' . $filename;

    if (!imagewebp($image, $destination, 82)) {
        imagedestroy($image);
        @unlink($destination);
        set_flash('post_error', 'Image could not be saved. Please try again later.');
        header('Location: index.php');
        exit;
    }

    imagedestroy($image);

    $imagePath = 'uploads/posts/' . $filename;
}

if (($content !== '' && postCharacterCount($content) <= (int)$maxPostLength) || $imagePath !== null) {
    $stmt = db()->prepare(
        'INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)'
    );

    $stmt->execute([
        $user['id'],
        $content,
        $imagePath
    ]);
}

header('Location: index.php');
