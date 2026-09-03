<?php
declare(strict_types=1);

const DB_PATH = __DIR__ . '/../data/microblog.sqlite';
const POST_URL_LENGTH = 23;
const POST_URL_PATTERN = '~https?://[^\s<]+~i';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, username FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

function postCharacterCount(string $content): int
{
    $count = 0;
    $offset = 0;

    if (preg_match_all(POST_URL_PATTERN, $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $url = $match[0];
            $position = $match[1];

            $count += mb_strlen(substr($content, $offset, $position - $offset));

            $trimmedUrl = rtrim($url, '.,!?;:)]}');
            $trailing = substr($url, strlen($trimmedUrl));

            $count += POST_URL_LENGTH;
            $count += mb_strlen($trailing);

            $offset = $position + strlen($url);
        }
    }

    $count += mb_strlen(substr($content, $offset));
    return $count;
}

function renderPostContent(string $content): string
{
    $output = '';
    $offset = 0;

    if (preg_match_all(POST_URL_PATTERN, $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $url = $match[0];
            $position = $match[1];

            $output .= e(substr($content, $offset, $position - $offset));

            $trimmedUrl = rtrim($url, '.,!?;:)]}');
            $trailing = substr($url, strlen($trimmedUrl));

            $output .= '<a class="post-link" href="' . e($trimmedUrl) . '" target="_blank" rel="noopener noreferrer">' . 'link' . '</a>';

            $output .= e($trailing);
            $offset = $position + strlen($url);
        }
    }

    $output .= e(substr($content, $offset));

    return nl2br($output);
}

function formatPostDate(string $date): string
{
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return '1 min ago';
    }

    if ($diff < 3600) {
        $minutes = (int) floor($diff / 60);
        return $minutes . ' min' . ($minutes === 1 ? '' : 's') . ' ago';
    }

    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' hr' . ($hours === 1 ? '' : 's') . ' ago';
    }

    if ($diff < 604800) {
        $days = (int) floor($diff / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    if ($diff < 2592000) {
        $weeks = (int) floor($diff / 604800);
        return $weeks . ' wk' . ($weeks === 1 ? '' : 's') . ' ago';
    }

    return date('M j, Y', $timestamp);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
