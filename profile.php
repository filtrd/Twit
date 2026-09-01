<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

$username = trim($_GET['u'] ?? '');
$stmt = db()->prepare('SELECT id, username, created_at FROM users WHERE username = ?');
$stmt->execute([$username]);
$profile = $stmt->fetch();
if (!$profile) { http_response_code(404); exit('User not found'); }
$stmt = db()->prepare('SELECT id, content, created_at FROM posts WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([$profile['id']]);
$posts = $stmt->fetchAll();
$user = current_user();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@<?= e($profile['username']) ?> - <?= e($siteName) ?></title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><div class="wrap"><a class="logo" href="index.php"><?= e($siteName) ?></a><nav><?php if ($user): ?><a href="profile.php?u=<?= urlencode($user['username']) ?>">@<?= e($user['username']) ?></a><?php else: ?><a href="login.php">Log in</a><?php endif; ?></nav></div></header><main class="wrap"><section class="profile"><h1>@<?= e($profile['username']) ?></h1><p>Joined <?= e($profile['created_at']) ?></p></section><section class="feed"><?php foreach ($posts as $post): ?><article class="post"><div class="post-head"><strong>@<?= e($profile['username']) ?></strong><time><?= e($post['created_at']) ?></time></div><p><?= nl2br(e($post['content'])) ?></p></article><?php endforeach; ?><?php if (!$posts): ?><p class="empty">No posts yet.</p><?php endif; ?></section></main></body></html>
