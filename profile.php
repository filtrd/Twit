<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

$username = trim($_GET['u'] ?? '');
$stmt = db()->prepare('SELECT id, username, avatar_path, created_at FROM users WHERE username = ?');
$stmt->execute([$username]);
$profile = $stmt->fetch();
if (!$profile) { http_response_code(404); exit('User not found'); }

$stmt = db()->prepare('SELECT p.id, p.content, p.image_path, p.created_at, p.edit_count, u.id AS user_id, u.username, u.avatar_path, (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count FROM posts p JOIN users u ON u.id = p.user_id WHERE p.user_id = ? ORDER BY p.id DESC');
$stmt->execute([$profile['id']]);
$posts = $stmt->fetchAll();

$stmt = db()->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
$stmt->execute([$profile['id']]);
$postCount = (int)$stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
$stmt->execute([$profile['id']]);
$followerCount = (int)$stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
$stmt->execute([$profile['id']]);
$followingCount = (int)$stmt->fetchColumn();

$user = current_user();
$isFollowing = false;
if ($user && (int)$user['id'] !== (int)$profile['id']) {
    $stmt = db()->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->execute([$user['id'], $profile['id']]);
    $isFollowing = (bool)$stmt->fetchColumn();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@<?= e($profile['username']) ?> · <?= e($siteName) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="wrap">
        <div class="brand">
            <a class="logo" href="index.php"><?= e($siteName) ?></a>
            <p class="tagline"><?= e($tagLine) ?></p>
        </div>
        <nav>
            <a href="./">Home</a>
            <?php if ($user): ?>
                <a href="profile.php?u=<?= urlencode($user['username']) ?>">@<?= e($user['username']) ?></a>
                <form class="inline" method="post" action="logout.php">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <button>Log out</button>
                </form>
            <?php else: ?>
                <a href="login.php">Log in</a>
                <a class="button" href="register.php">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
    <div class="wrap">
        <section class="profile">
            <div class="profile-main">
                <?php if (!empty($profile['avatar_path'])): ?>
                    <img class="avatar avatar-profile" src="<?= e($profile['avatar_path']) ?>" alt="">
                <?php else: ?>
                    <span class="avatar avatar-profile avatar-fallback"><?= e(strtoupper(substr($profile['username'], 0, 1))) ?></span>
                <?php endif; ?>
                <div>
                    <h1>@<?= e($profile['username']) ?></h1>
                    <p>Joined <?= date('M Y', strtotime($profile['created_at'])) ?></p>
                    <p><?= $postCount ?> Posts &middot; <?= $followerCount ?> Followers &middot; <?= $followingCount ?> Following</p>
                </div>
            </div>

            <?php if ($user && (int)$user['id'] === (int)$profile['id']): ?>
                <form class="avatar-form" method="post" action="avatar.php" enctype="multipart/form-data">
                    <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <button type="button" class="button" id="avatar-button"><?= !empty($profile['avatar_path']) ? 'Change avatar' : 'Upload avatar' ?></button>
                    <?php if (!empty($profile['avatar_path'])): ?>
                        <button type="submit" class="button" name="remove" value="1">Remove avatar</button>
                    <?php endif; ?>
                </form>
            <?php elseif ($user): ?>
                <form method="post" action="follow.php">
                    <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <button class="button"><?= $isFollowing ? 'Unfollow' : 'Follow' ?></button>
                </form>
            <?php endif; ?>
        </section>

        <section class="feed">
            <?php foreach ($posts as $post): ?>
                <?php renderPost($post, $user, 'profile'); ?>
            <?php endforeach; ?>

            <?php if (!$posts): ?>
                <p class="empty">No posts yet.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<footer>
    <div class="wrap">
        <span>&copy; <?= date('Y') ?> <?= e($siteName) ?></span>
        <nav><a href="#">About</a><a href="#">Privacy</a><a href="#">Terms</a></nav>
    </div>
</footer>

<script>
const avatarButton = document.getElementById('avatar-button');
const avatarUpload = document.getElementById('avatar-upload');

if (avatarButton && avatarUpload) {
    avatarButton.addEventListener('click', () => avatarUpload.click());
    avatarUpload.addEventListener('change', () => {
        if (avatarUpload.files.length) {
            avatarUpload.closest('form').submit();
        }
    });
}

document.querySelectorAll('.post-menu').forEach(menu => {
    const button = menu.querySelector('.post-menu-button');
    const dropdown = menu.querySelector('.post-menu-dropdown');

    button.addEventListener('click', event => {
        event.stopPropagation();
        const open = !dropdown.hidden;
        document.querySelectorAll('.post-menu-dropdown').forEach(item => item.hidden = true);
        document.querySelectorAll('.post-menu-button').forEach(item => item.setAttribute('aria-expanded', 'false'));
        dropdown.hidden = open;
        button.setAttribute('aria-expanded', String(!open));
    });
});

document.addEventListener('click', () => {
    document.querySelectorAll('.post-menu-dropdown').forEach(item => item.hidden = true);
    document.querySelectorAll('.post-menu-button').forEach(item => item.setAttribute('aria-expanded', 'false'));
});
</script>
</body>
</html>
