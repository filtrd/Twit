<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

$username = trim($_GET['u'] ?? '');
$stmt = db()->prepare('SELECT id, username, created_at FROM users WHERE username = ?');
$stmt->execute([$username]);
$profile = $stmt->fetch();
if (!$profile) { http_response_code(404); exit('User not found'); }

$stmt = db()->prepare('SELECT id, content, image_path, created_at FROM posts WHERE user_id = ? ORDER BY id DESC');
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
<title>@<?= e($profile['username']) ?> - <?= e($siteName) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="wrap">
        <a class="logo" href="index.php"><?= e($siteName) ?></a>
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
            <h1>@<?= e($profile['username']) ?></h1>
            <p>Joined <?= date('M Y', strtotime($profile['created_at'])) ?></p>
            <p><?= $postCount ?> Posts &middot; <?= $followerCount ?> Followers &middot; <?= $followingCount ?> Following</p>
            <?php if ($user && (int)$user['id'] !== (int)$profile['id']): ?>
                <form method="post" action="follow.php">
                    <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <button class="button"><?= $isFollowing ? 'Unfollow' : 'Follow' ?></button>
                </form>
            <?php endif; ?>
        </section>

        <section class="feed">
            <?php foreach ($posts as $post): ?>
                <article class="post">
                    <div class="post-head">
                        <strong>@<?= e($profile['username']) ?></strong>
                        <time><?= e(formatPostDate($post['created_at'])) ?></time>
                    </div>
                    <?php if ($post['content'] !== ''): ?>
                        <p><?= nl2br(e($post['content'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($post['image_path'])): ?>
                        <img class="post-image" src="<?= e($post['image_path']) ?>" alt="">
                    <?php endif; ?>
                </article>
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
        <nav>
            <a href="#">About</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
        </nav>
    </div>
</footer>
</body>
</html>
