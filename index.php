<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/config.php';

$user = current_user();
$postError = get_flash('post_error');
$postDraft = get_flash('post_draft') ?? '';
$stmt = db()->query(<<<'SQL'
SELECT p.id, p.content, p.image_path, p.created_at, p.updated_at, p.edit_count, u.id AS user_id, u.username, u.avatar_path,
       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
FROM posts p
JOIN users u ON u.id = p.user_id
ORDER BY p.id DESC
SQL);
$posts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($siteName) ?> · <?= e($tagLine) ?></title>
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
        <?php if ($user): ?>
            <form class="composer" method="post" action="post.php" enctype="multipart/form-data">
                <textarea name="content" placeholder="What's happening?"><?= e($postDraft) ?></textarea>
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
            <?php if ($postError): ?>
                <p class="form-error"><?= e($postError) ?></p>
            <?php endif; ?>
        <?php elseif ($postError): ?>
            <p class="form-error"><?= e($postError) ?></p>
        <?php endif; ?>

        <section class="feed">
            <?php foreach ($posts as $post): ?>
                <?php renderPost($post, $user); ?>
            <?php endforeach; ?>

            <?php if (!$posts): ?>
                <p class="empty">No posts yet. Be the first!</p>
            <?php endif; ?>
        </section>
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
    <div class="wrap">
        <span>&copy; <?= date('Y') ?> <?= e($siteName) ?></span>
        <nav><a href="#">About</a><a href="#">Privacy</a><a href="#">Terms</a></nav>
    </div>
</footer>

<script>
const textarea = document.querySelector('textarea[name="content"]');
const counter = document.getElementById('char-count');
const imageButton = document.getElementById('image-button');
const imageUpload = document.getElementById('image-upload');
const selectedImage = document.getElementById('selected-image');
const emojiButton = document.getElementById('emoji-button');
const emojiPicker = document.getElementById('emoji-picker');
const deleteDialog = document.getElementById('delete-dialog');
const maxPostLength = <?= (int)$maxPostLength ?>;
let pendingDeleteForm = null;

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
    if (textarea && counter) counter.textContent = postCharacterCount(textarea.value) + '/' + maxPostLength;
}

function enforcePostLength() {
    if (!textarea) return;
    const characters = Array.from(textarea.value);
    if (postCharacterCount(textarea.value) <= maxPostLength) return;
    let low = 0, high = characters.length;
    while (low < high) {
        const mid = Math.ceil((low + high) / 2);
        const candidate = characters.slice(0, mid).join('');
        if (postCharacterCount(candidate) <= maxPostLength) low = mid;
        else high = mid - 1;
    }
    const cursor = textarea.selectionStart;
    textarea.value = characters.slice(0, low).join('');
    const newCursor = Math.min(cursor, textarea.value.length);
    textarea.selectionStart = newCursor;
    textarea.selectionEnd = newCursor;
    updateCounter();
}

if (textarea && counter) {
    textarea.addEventListener('input', () => { enforcePostLength(); updateCounter(); });
    updateCounter();
}

if (imageButton && imageUpload) {
    imageButton.addEventListener('click', () => imageUpload.click());
    imageUpload.addEventListener('change', () => { selectedImage.textContent = imageUpload.files.length ? imageUpload.files[0].name : ''; });
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
            enforcePostLength();
            updateCounter();
            emojiPicker.hidden = true;
        });
    });
}

document.querySelectorAll('.post-delete-form').forEach(form => {
    form.addEventListener('submit', event => {
        if (!deleteDialog) return;
        event.preventDefault();
        pendingDeleteForm = form;
        deleteDialog.showModal();
    });
});

if (deleteDialog) {
    deleteDialog.addEventListener('close', () => {
        if (deleteDialog.returnValue === 'confirm' && pendingDeleteForm) {
            pendingDeleteForm.submit();
        }
        pendingDeleteForm = null;
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
