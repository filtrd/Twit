<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = db();

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    avatar_path TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    image_path TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS likes (
    user_id INTEGER NOT NULL,
    post_id INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS follows (
    follower_id INTEGER NOT NULL,
    following_id INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    CHECK (follower_id != following_id)
);
SQL);

/*
 * Add avatar_path for databases created before avatar uploads were added.
 */
$columns = $pdo->query('PRAGMA table_info(users)')->fetchAll();

$hasAvatarPath = false;

foreach ($columns as $column) {
    if ($column['name'] === 'avatar_path') {
        $hasAvatarPath = true;
        break;
    }
}

if (!$hasAvatarPath) {
    $pdo->exec('ALTER TABLE users ADD COLUMN avatar_path TEXT');
}

/*
 * Older databases had CHECK(length(content) <= 280) on posts.content.
 * That conflicts with URL-aware counting because a stored URL can be
 * longer than 280 characters while counting as only 23 characters.
 *
 * SQLite cannot remove a CHECK constraint with ALTER TABLE, so rebuild
 * posts and likes when the old constraint is detected. Existing data and
 * likes are preserved.
 */
$tableSql = $pdo->query(
    "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'posts'"
)->fetchColumn();

if (
    is_string($tableSql)
    && stripos($tableSql, 'CHECK(length(content) <= 280)') !== false
) {
    $pdo->exec('PRAGMA foreign_keys = OFF');

    try {
        $pdo->beginTransaction();

        $pdo->exec('ALTER TABLE likes RENAME TO likes_old');
        $pdo->exec('ALTER TABLE posts RENAME TO posts_old');

        $pdo->exec(<<<'SQL'
CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    image_path TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL);

        $pdo->exec(<<<'SQL'
INSERT INTO posts (id, user_id, content, image_path, created_at)
SELECT id, user_id, content, image_path, created_at
FROM posts_old
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE likes (
    user_id INTEGER NOT NULL,
    post_id INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
SQL);

        $pdo->exec(<<<'SQL'
INSERT INTO likes (user_id, post_id, created_at)
SELECT user_id, post_id, created_at
FROM likes_old
SQL);

        $pdo->exec('DROP TABLE likes_old');
        $pdo->exec('DROP TABLE posts_old');

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        throw $e;
    }

    $pdo->exec('PRAGMA foreign_keys = ON');
}

/*
 * Add post fields for databases created before image uploads/editing were added.
 */
$columns = $pdo->query('PRAGMA table_info(posts)')->fetchAll();

$hasImagePath = false;
$hasEditCount = false;

foreach ($columns as $column) {
    if ($column['name'] === 'image_path') {
        $hasImagePath = true;
    }
    if ($column['name'] === 'edit_count') {
        $hasEditCount = true;
    }
}

if (!$hasImagePath) {
    $pdo->exec('ALTER TABLE posts ADD COLUMN image_path TEXT');
}

if (!$hasEditCount) {
    $pdo->exec('ALTER TABLE posts ADD COLUMN edit_count INTEGER NOT NULL DEFAULT 0');
}

/*
 * Add comments/replies. parent_id is self-referential so replies can form
 * threaded conversations. Repair the table if an earlier posts migration
 * left its post foreign key pointing at posts_old.
 */
$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    parent_id INTEGER,
    content TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);
SQL);

$commentForeignKeys = $pdo->query('PRAGMA foreign_key_list(comments)')->fetchAll();
$commentsNeedRepair = false;

foreach ($commentForeignKeys as $foreignKey) {
    if ($foreignKey['table'] !== 'posts' && $foreignKey['table'] !== 'users' && $foreignKey['table'] !== 'comments') {
        $commentsNeedRepair = true;
        break;
    }
}

if ($commentsNeedRepair) {
    $pdo->exec('PRAGMA foreign_keys = OFF');

    try {
        $pdo->beginTransaction();

        $pdo->exec('ALTER TABLE comments RENAME TO comments_old');

        $pdo->exec(<<<'SQL'
CREATE TABLE comments_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    parent_id INTEGER,
    content TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments_new(id) ON DELETE CASCADE
);
SQL);

        $pdo->exec(<<<'SQL'
INSERT INTO comments_new (id, post_id, user_id, parent_id, content, created_at)
SELECT id, post_id, user_id, parent_id, content, created_at
FROM comments_old
SQL);

        $pdo->exec('DROP TABLE comments_old');
        $pdo->exec('ALTER TABLE comments_new RENAME TO comments');

        $pdo->exec('CREATE INDEX IF NOT EXISTS comments_post_id_idx ON comments(post_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS comments_parent_id_idx ON comments(parent_id)');

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        throw $e;
    }

    $pdo->exec('PRAGMA foreign_keys = ON');
}

$pdo->exec('CREATE INDEX IF NOT EXISTS comments_post_id_idx ON comments(post_id)');
$pdo->exec('CREATE INDEX IF NOT EXISTS comments_parent_id_idx ON comments(parent_id)');
