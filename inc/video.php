<?php
declare(strict_types=1);

function detectVideo(string $url): ?array
{
    $parsed = parse_url($url);
    $host = strtolower($parsed['host'] ?? '');
    $host = preg_replace('~^www\\.~', '', $host);
    $path = trim($parsed['path'] ?? '', '/');
    $segments = $path === '' ? [] : explode('/', $path);

    if ($host === 'youtube.com' || $host === 'm.youtube.com') {
        if (($segments[0] ?? '') === 'watch') {
            parse_str($parsed['query'] ?? '', $query);
            $id = $query['v'] ?? '';
            if (is_string($id) && preg_match('~^[A-Za-z0-9_-]{11}$~', $id)) return ['provider' => 'youtube', 'id' => $id];
        }
        if (($segments[0] ?? '') === 'shorts' && isset($segments[1]) && preg_match('~^[A-Za-z0-9_-]{11}$~', $segments[1])) return ['provider' => 'youtube', 'id' => $segments[1]];
    }

    if ($host === 'youtu.be' && isset($segments[0]) && preg_match('~^[A-Za-z0-9_-]{11}$~', $segments[0])) return ['provider' => 'youtube', 'id' => $segments[0]];

    if ($host === 'tiktok.com' || $host === 'm.tiktok.com') {
        if (($segments[1] ?? '') === 'video' && isset($segments[2]) && preg_match('~^\d{1,30}$~', $segments[2])) return ['provider' => 'tiktok', 'id' => $segments[2]];
    }

    if ($host === 'instagram.com') {
        if (in_array($segments[0] ?? '', ['p', 'reel'], true) && isset($segments[1]) && preg_match('~^[A-Za-z0-9_-]+$~', $segments[1])) return ['provider' => 'instagram', 'type' => $segments[0], 'id' => $segments[1]];
    }

    return null;
}
