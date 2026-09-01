<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

$user = current_user();
$stmt = db()->query(<<<'SQL'
SELECT p.id, p.content, p.created_at, u.id AS user_id, u.username,
       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count
FROM posts p
JOIN users u ON u.id = p.user_id
ORDER BY p.id DESC
SQL);
$posts = $stmt->fetchAll();

function liked_by_me(int $postId): bool {
    if (!isset($_SESSION['user_id'])) return false;
    $stmt = db()->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
    $stmt->execute([$_SESSION['user_id'], $postId]);
    return (bool) $stmt->fetchColumn();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Twit</title><link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar"><div class="wrap"><a class="logo" href="index.php">Twit</a><nav>
<?php if ($user): ?><a href="profile.php?u=<?= urlencode($user['username']) ?>">@<?= e($user['username']) ?></a><form class="inline" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button>Log out</button></form><?php else: ?><a href="login.php">Log in</a><a class="button" href="register.php">Sign up</a><?php endif; ?>
</nav></div></header>
<main class="wrap">
<?php if ($user): ?><form class="composer" method="post" action="post.php"><textarea name="content" maxlength="280" placeholder="What's happening?" required></textarea><div><span>280 characters max</span><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="button">Twit</button></div></form><?php else: ?><section class="hero"><h1>Welcome to Twit</h1><p>A tiny, simple Twitter-style PHP app.</p><a class="button" href="register.php">Create an account</a></section><?php endif; ?>
<section class="feed">
<?php foreach ($posts as $post): ?><article class="post"><div class="post-head"><a href="profile.php?u=<?= urlencode($post['username']) ?>"><strong>@<?= e($post['username']) ?></strong></a><time><?= e($post['created_at']) ?></time></div><p><?= nl2br(e($post['content'])) ?></p><div class="post-actions"><span>♥ <?= (int)$post['like_count'] ?></span><?php if ($user): ?><form class="inline" method="post" action="like.php"><input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button><?= liked_by_me((int)$post['id']) ? 'Unlike' : 'Like' ?></button></form><?php endif; ?></div></article><?php endforeach; ?>
<?php if (!$posts): ?><p class="empty">No twits yet. Be the first!</p><?php endif; ?>
</section></main></body></html>
