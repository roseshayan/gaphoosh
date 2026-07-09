<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class RateLimiter
{
    public function __construct(private readonly Database $db)
    {
    }

    public function hit(string $key, int $maxAttempts, int $windowSeconds, int $blockSeconds = 0): void
    {
        $key = $this->normalizeKey($key);
        $maxAttempts = max(1, $maxAttempts);
        $windowSeconds = max(1, $windowSeconds);
        $blockSeconds = max(0, $blockSeconds);

        $row = $this->db->fetch('SELECT * FROM rate_limits WHERE rate_key = :rate_key LIMIT 1', ['rate_key' => $key]);
        $now = time();

        if ($row && !empty($row['blocked_until']) && strtotime((string) $row['blocked_until']) > $now) {
            $seconds = max(1, strtotime((string) $row['blocked_until']) - $now);
            throw new RuntimeException('درخواست‌های شما بیش از حد مجاز است. حدود ' . $seconds . ' ثانیه دیگر دوباره تلاش کنید.', 429);
        }

        if (!$row) {
            $this->db->insert(
                'INSERT INTO rate_limits (rate_key, attempts, window_start, blocked_until, created_at, updated_at) VALUES (:rate_key, 1, NOW(), NULL, NOW(), NOW())',
                ['rate_key' => $key]
            );
            return;
        }

        $windowStart = strtotime((string) $row['window_start']);
        $attempts = (int) ($row['attempts'] ?? 0);

        if ($windowStart <= 0 || ($now - $windowStart) >= $windowSeconds) {
            $this->db->execute(
                'UPDATE rate_limits SET attempts = 1, window_start = NOW(), blocked_until = NULL, updated_at = NOW() WHERE rate_key = :rate_key',
                ['rate_key' => $key]
            );
            return;
        }

        $attempts++;
        if ($attempts > $maxAttempts) {
            $blockedUntil = $blockSeconds > 0 ? date('Y-m-d H:i:s', $now + $blockSeconds) : date('Y-m-d H:i:s', $windowStart + $windowSeconds);
            $this->db->execute(
                'UPDATE rate_limits SET attempts = :attempts, blocked_until = :blocked_until, updated_at = NOW() WHERE rate_key = :rate_key',
                ['attempts' => $attempts, 'blocked_until' => $blockedUntil, 'rate_key' => $key]
            );
            throw new RuntimeException('درخواست‌های شما بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', 429);
        }

        $this->db->execute(
            'UPDATE rate_limits SET attempts = :attempts, updated_at = NOW() WHERE rate_key = :rate_key',
            ['attempts' => $attempts, 'rate_key' => $key]
        );
    }

    public function clear(string $key): void
    {
        $this->db->execute('DELETE FROM rate_limits WHERE rate_key = :rate_key', ['rate_key' => $this->normalizeKey($key)]);
    }

    private function normalizeKey(string $key): string
    {
        $key = trim(mb_strtolower($key, 'UTF-8'));
        if ($key === '') {
            $key = 'empty';
        }
        return mb_substr($key, 0, 190, 'UTF-8');
    }
}
