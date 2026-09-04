<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

$username = trim($_GET['u'] ?? '');
$stmt = db()->prepare('SELECT id, username, avatar_path, created_at FROM users WHERE username = ?');
$stmt->execute([$username]);
$profile = $stmt->fetch();
if (!$profile) { http_response_code(404); exit('User not found'); }

$feedPageSize = (int)$feedPageSize;
$feedQueryLimit = $feedPageSize + 1;

$stmt = db()->prepare('SELECT p.id, p.content, p.image_path, p.created_at, p.updated_at, p.edit_count, u.id AS user_id, u.username, u.avatar_path, (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count, (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count FROM posts p JOIN users u ON u.id = p.user_id WHERE p.user_id = ? ORDER BY p.created_at DESC, p.id DESC LIMIT ' . $feedQueryLimit);
$stmt->execute([$profile['id']]);
$posts = $stmt->fetchAll();

$hasMorePosts = count($posts) > $feedPageSize;
if ($hasMorePosts) array_pop($posts);

$nextFeedCursor = null;
if ($hasMorePosts && $posts) {
    $lastPost = $posts[array_key_last($posts)];
    $cursorPayload = json_encode([
        'created_at' => $lastPost['created_at'],
        'id' => (int)$lastPost['id'],
    ], JSON_THROW_ON_ERROR);
    $nextFeedCursor = rtrim(strtr(base64_encode($cursorPayload), '+/', '-_'), '=');
}

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

$avatarError = trim($_GET['avatar_error'] ?? '');
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
                <form class="inline" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button>Log out</button></form>
            <?php else: ?>
                <a href="login.php">Log in</a><a class="button" href="register.php">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
    <div class="wrap">
        <section class="profile">
            <div class="profile-main">
                <?php if ($user && (int)$user['id'] === (int)$profile['id']): ?>
                    <form class="avatar-form" method="post" action="avatar.php" enctype="multipart/form-data">
                        <label class="avatar-upload" for="avatar-upload">
                            <?php if (!empty($profile['avatar_path'])): ?><img class="avatar avatar-profile" src="<?= e($profile['avatar_path']) ?>" alt="">
                            <?php else: ?><span class="avatar avatar-profile avatar-fallback"><?= e(strtoupper(substr($profile['username'], 0, 1))) ?></span><?php endif; ?>
                        </label>
                        <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    </form>
                <?php elseif (!empty($profile['avatar_path'])): ?><img class="avatar avatar-profile" src="<?= e($profile['avatar_path']) ?>" alt="">
                <?php else: ?><span class="avatar avatar-profile avatar-fallback"><?= e(strtoupper(substr($profile['username'], 0, 1))) ?></span><?php endif; ?>
                <div class="profile-main-info">
                    <div class="profile-name">
                        <h1>@<?= e($profile['username']) ?></h1>
                    </div>
                    <p>Joined <?= date('M Y', strtotime($profile['created_at'])) ?></p>
                    <p><?= $postCount ?> Posts &middot; <?= $followerCount ?> Followers &middot; <?= $followingCount ?> Following</p>
                    <?php if ($user && (int)$user['id'] !== (int)$profile['id']): ?>
                        <form class="follow-form" method="post" action="follow.php"><input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="button"><?= $isFollowing ? 'Following' : 'Follow' ?></button></form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($avatarError): ?><p class="error"><?= e($avatarError) ?></p><?php endif; ?>
        </section>

        <section class="feed" id="feed" data-profile-username="<?= e($profile['username']) ?>" data-next-cursor="<?= e($nextFeedCursor ?? '') ?>" data-has-more="<?= $hasMorePosts ? '1' : '0' ?>">
            <?php foreach ($posts as $post): ?>
                <?php renderPost($post, $user, 'profile'); ?>
            <?php endforeach; ?>
            <?php if (!$posts): ?><p class="empty">No posts yet.</p><?php endif; ?>
        </section>
        <div id="feed-sentinel" aria-hidden="true"></div>
        <?php if ($hasMorePosts): ?><p id="feed-status" class="empty" hidden>Loading more posts…</p><?php endif; ?>
    </div>
</main>

<dialog id="delete-dialog" aria-labelledby="delete-dialog-title">
    <p id="delete-dialog-title">Delete this post?</p>
    <form method="dialog">
        <button type="submit" value="cancel">Cancel</button>
        <button type="submit" value="confirm" autofocus>Delete</button>
    </form>
</dialog>

<footer>
    <div class="wrap"><span>&copy; <?= date('Y') ?> <?= e($siteName) ?></span><nav><a href="#">About</a><a href="#">Privacy</a><a href="#">Terms</a></nav></div>
</footer>

<script type="module" src="assets/js/profile.js"></script>
</body>
</html>
