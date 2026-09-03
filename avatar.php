<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/database.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php?u=' . urlencode($user['username']));
    exit;
}

verify_csrf();

$profileUrl = 'profile.php?u=' . urlencode($user['username']);

if (isset($_POST['remove'])) {
    if (!empty($user['avatar_path'])) {
        @unlink(__DIR__ . '/' . $user['avatar_path']);
    }

    $stmt = db()->prepare('UPDATE users SET avatar_path = NULL WHERE id = ?');
    $stmt->execute([$user['id']]);

    header('Location: ' . $profileUrl);
    exit;
}

if (empty($_FILES['avatar']['name'])) {
    header('Location: ' . $profileUrl);
    exit;
}

if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ' . $profileUrl);
    exit;
}

if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
    header('Location: ' . $profileUrl);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['avatar']['tmp_name']);

$allowedMimes = [
    'image/jpeg',
    'image/png',
    'image/webp',
];

if (!in_array($mime, $allowedMimes, true)) {
    header('Location: ' . $profileUrl);
    exit;
}

if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled') || !function_exists('imagewebp')) {
    header('Location: ' . $profileUrl);
    exit;
}

$image = imagecreatefromstring(file_get_contents($_FILES['avatar']['tmp_name']));

if ($image === false) {
    header('Location: ' . $profileUrl);
    exit;
}

$width = imagesx($image);
$height = imagesy($image);
$size = min($width, $height);
$sourceX = (int) floor(($width - $size) / 2);
$sourceY = (int) floor(($height - $size) / 2);

$avatar = imagecreatetruecolor(150, 150);
$transparent = imagecolorallocatealpha($avatar, 0, 0, 0, 127);
imagefill($avatar, 0, 0, $transparent);
imagealphablending($avatar, false);
imagesavealpha($avatar, true);

imagecopyresampled(
    $avatar,
    $image,
    0,
    0,
    $sourceX,
    $sourceY,
    150,
    150,
    $size,
    $size
);

imagedestroy($image);

$uploadDir = __DIR__ . '/uploads/avatars';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$filename = bin2hex(random_bytes(16)) . '.webp';
$destination = $uploadDir . '/' . $filename;

if (!imagewebp($avatar, $destination, 82)) {
    imagedestroy($avatar);
    @unlink($destination);
    header('Location: ' . $profileUrl);
    exit;
}

imagedestroy($avatar);

$avatarPath = 'uploads/avatars/' . $filename;

$stmt = db()->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
$stmt->execute([$avatarPath, $user['id']]);

if (!empty($user['avatar_path'])) {
    @unlink(__DIR__ . '/' . $user['avatar_path']);
}

header('Location: ' . $profileUrl);
