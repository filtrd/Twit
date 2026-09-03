<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

$user = current_user();
$stmt = db()->query(<<<'SQL'
SELECT p.id, p.content, p.image_path, p.created_at, u.id AS user_id, u.username, u.avatar_path,
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
<title><?= e($siteName) ?></title><link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="wrap">
        <div class="brand">
            <a class="logo" href="index.php"><?= e($siteName) ?></a>
            <p class="tagline">Share short thoughts with the world.</p>
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
        <?php if ($user): ?>
            <form class="composer" method="post" action="post.php" enctype="multipart/form-data">
                <textarea name="content" placeholder="What's happening?"></textarea>
                <input type="file" id="image-upload" name="image" accept="image/jpeg,image/png,image/webp" hidden>

                <div class="composer-tools">
                    <div class="composer-shortcuts">
                        <button type="button" class="icon-button" id="image-button" aria-label="Add image" title="Add image">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="1"></rect><circle cx="8" cy="9" r="1.5"></circle><path d="M4 17l5-5 3.5 3.5 2.5-2.5 5 5"></path></svg>
                        </button>
                        <button type="button" class="icon-button" id="emoji-button" aria-label="Add emoji" title="Add emoji">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><circle cx="9" cy="10" r="1"></circle><circle cx="15" cy="10" r="1"></circle><path d="M8.5 14.5c1 1.5 2.2 2.25 3.5 2.25s2.5-.75 3.5-2.25"></path></svg>
                        </button>
                        <div class="emoji-picker" id="emoji-picker" hidden>
                            <button type="button">😀</button><button type="button">😂</button><button type="button">❤️</button><button type="button">👍</button><button type="button">🎉</button><button type="button">🔥</button><button type="button">🚀</button><button type="button">😊</button><button type="button">😎</button><button type="button">🤔</button><button type="button">👏</button><button type="button">🙌</button>
                        </div>
                    </div>
                    <div class="composer-meta">
                        <span id="selected-image"></span>
                        <span id="char-count">0/<?= (int)$maxPostLength ?></span>
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <button class="button">Post</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <section class="feed">
            <?php foreach ($posts as $post): ?>
                <article class="post">
                    <div class="post-head">
                        <a class="avatar-link" href="profile.php?u=<?= urlencode($post['username']) ?>">
                            <?php if (!empty($post['avatar_path'])): ?>
                                <img class="avatar avatar-small" src="<?= e($post['avatar_path']) ?>" alt="">
                            <?php else: ?>
                                <span class="avatar avatar-small avatar-fallback"><?= e(strtoupper(substr($post['username'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="post-author">
                            <a href="profile.php?u=<?= urlencode($post['username']) ?>"><strong>@<?= e($post['username']) ?></strong></a>
                            <time><?= e(formatPostDate($post['created_at'])) ?></time>
                        </div>
                    </div>
                    <?php if ($post['content'] !== ''): ?>
                        <p><?= renderPostContent($post['content']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($post['image_path'])): ?>
                        <img class="post-image" src="<?= e($post['image_path']) ?>" alt="">
                    <?php endif; ?>
                    <div class="post-actions">
                        <span>♥ <?= (int)$post['like_count'] ?></span>
                        <?php if ($user): ?>
                            <form class="inline" method="post" action="like.php">
                                <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <button><?= liked_by_me((int)$post['id']) ? 'Unlike' : 'Like' ?></button>
                            </form>
                            <?php if ((int)$post['user_id'] === (int)$user['id']): ?>
                                <form class="inline" method="post" action="delete.php" onsubmit="return confirm('Delete this post?');">
                                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!$posts): ?>
                <p class="empty">No posts yet. Be the first!</p>
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
const textarea = document.querySelector('textarea[name="content"]);
const counter = document.getElementById('char-count');
const imageButton = document.getElementById('image-button');
const imageUpload = document.getElementById('image-upload');
const selectedImage = document.getElementById('selected-image');
const emojiButton = document.getElementById('emoji-button');
const emojiPicker = document.getElementById('emoji-picker');

function postCharacterCount(value) {
    let count = 0;
    let lastIndex = 0;
    const urlPattern = /https?:\/\/[^\s<]+/gi;
    let match;

    while ((match = urlPattern.exec(value)) !== null) {
        count += Array.from(value.slice(lastIndex, match.index)).length;
        const url = match[0];
        const trailingMatch = url.match(/[.,!?;:)\]}]+$/);
        const trailing = trailingMatch ? trailingMatch[0] : '';
        count += 23;
        count += Array.from(trailing).length;
        lastIndex = match.index + url.length;
    }

    count += Array.from(value.slice(lastIndex)).length;
    return count;
}

function updateCounter() {
    if (textarea && counter) {
        counter.textContent = postCharacterCount(textarea.value) + '/<?= (int)$maxPostLength ?>';
    }
}

if (textarea && counter) {
    textarea.addEventListener('input', updateCounter);
    updateCounter();
}

if (imageButton && imageUpload) {
    imageButton.addEventListener('click', () => imageUpload.click());
    imageUpload.addEventListener('change', () => {
        selectedImage.textContent = imageUpload.files.length ? imageUpload.files[0].name : '';
    });
}

if (emojiButton && emojiPicker) {
    emojiButton.addEventListener('click', () => { emojiPicker.hidden = !emojiPicker.hidden; });
    emojiPicker.querySelectorAll('button').forEach(button => {
        button.addEventListener('click', () => {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const emoji = button.textContent;
            textarea.value = textarea.value.slice(0, start) + emoji + textarea.value.slice(end);
            textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
            textarea.focus();
            updateCounter();
            emojiPicker.hidden = true;
        });
    });
}
</script>
</body>
</html>
