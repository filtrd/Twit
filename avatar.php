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
$maxAvatarSize = 2 * 1024 * 1024;

function avatar_error(string $message, string $profileUrl): never
{
    header('Location: ' . $profileUrl . '&avatar_error=' . urlencode($message));
    exit;
}

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
    avatar_error('Please select an image to upload.', $profileUrl);
}

$uploadError = $_FILES['avatar']['error'];

if ($uploadError !== UPLOAD_ERR_OK) {
    $message = match ($uploadError) {
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE => 'Avatar is too large. Maximum size is 2 MB.',
        UPLOAD_ERR_PARTIAL => 'Avatar upload was incomplete. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please select an image to upload.',
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE => 'Avatar upload failed. Please try again.',
        UPLOAD_ERR_EXTENSION => 'Avatar upload was stopped by the server.',
        default => 'Avatar upload failed. Please try again.',
    };

    avatar_error($message, $profileUrl);
}

if ($_FILES['avatar']['size'] > $maxAvatarSize) {
    avatar_error('Avatar is too large. Maximum size is 2 MB.', $profileUrl);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['avatar']['tmp_name']);

$allowedMimes = [
    'image/jpeg',
    'image/png',
    'image/webp',
];

if (!in_array($mime, $allowedMimes, true)) {
    avatar_error('Please upload a JPEG, PNG, or WebP image.', $profileUrl);
}

if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled') || !function_exists('imagewebp')) {
    avatar_error('Avatar processing is unavailable. Please contact the site administrator.', $profileUrl);
}

$image = imagecreatefromstring(file_get_contents($_FILES['avatar']['tmp_name']));

if ($image === false) {
    avatar_error("We couldn't process that image. Please try another one.", $profileUrl);
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
    avatar_error('Avatar upload failed. Please try again.', $profileUrl);
}

imagedestroy($avatar);

$avatarPath = 'uploads/avatars/' . $filename;

$stmt = db()->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
$stmt->execute([$avatarPath, $user['id']]);

if (!empty($user['avatar_path'])) {
    @unlink(__DIR__ . '/' . $user['avatar_path']);
}

header('Location: ' . $profileUrl);
