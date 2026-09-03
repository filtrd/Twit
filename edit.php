<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

$user = require_login();
$postId = (int)($_GET['post_id'] ?? $_POST['post_id'] ?? 0);

$stmt = db()->prepare('SELECT id, content, image_path, created_at, edit_count FROM posts WHERE id = ? AND user_id = ?');
$stmt->execute([$postId, $user['id']]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit;
}

$age = time() - strtotime($post['created_at']);
$canEdit = $age >= 0 && $age <= ((int)$editTime * 60) && (int)$post['edit_count'] < (int)$editCount;
$redirect = ($_POST['redirect'] ?? $_GET['redirect'] ?? '') === 'profile' ? 'profile' : 'index';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$canEdit) {
        $error = 'This post can no longer be edited.';
    } else {
        $content = trim($_POST['content'] ?? '');

        if ($content === '' && empty($post['image_path'])) {
            $error = 'Post cannot be empty.';
        } elseif (postCharacterCount($content) > $maxPostLength) {
            $error = 'Post is too long.';
        } else {
            $stmt = db()->prepare(
                'UPDATE posts SET content = ?, edit_count = edit_count + 1 WHERE id = ? AND user_id = ? AND edit_count < ?'
            );
            $stmt->execute([$content, $postId, $user['id'], (int)$editCount]);

            $target = $redirect === 'profile'
                ? 'profile.php?u=' . urlencode($user['username'])
                : 'index.php';
            header('Location: ' . $target);
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit post · <?= e($siteName) ?></title>
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
            <a href="profile.php?u=<?= urlencode($user['username']) ?>">@<?= e($user['username']) ?></a>
        </nav>
    </div>
</header>

<main>
    <div class="wrap">
        <section class="edit-post">
            <h1>Edit post</h1>
            <?php if ($error): ?>
                <p class="error"><?= e($error) ?></p>
            <?php endif; ?>
            <?php if ($canEdit): ?>
                <form method="post">
                    <textarea name="content" maxlength="10000"><?= e($post['content']) ?></textarea>
                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <div class="edit-post-actions">
                        <span>Up to <?= (int)$editCount ?> edits within <?= (int)$editTime ?> minutes</span>
                        <div>
                            <a href="<?= $redirect === 'profile' ? 'profile.php?u=' . urlencode($user['username']) : 'index.php' ?>">Cancel</a>
                            <button class="button" type="submit">Save</button>
                        </div>
                    </div>
                </form>
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
</body>
</html>
