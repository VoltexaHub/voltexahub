<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BruteForceProtection
{
    private const PREFIX = 'bf:ip:';
    private const BLOCKED_PREFIX = 'bf:blocked:';
    private const INDEX_KEY = 'bf:blocked_ips';
    private const THRESHOLD = 20;
    private const WINDOW_SECONDS = 3600; // 1 hour

    public function recordFailedAttempt(string $ip): void
    {
        $key = self::PREFIX . $ip;
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, self::WINDOW_SECONDS);

        if ($attempts >= self::THRESHOLD) {
            Cache::put(self::BLOCKED_PREFIX . $ip, [
                'attempts' => $attempts,
                'blocked_at' => now()->toIso8601String(),
                'expires_at' => now()->addSeconds(self::WINDOW_SECONDS)->toIso8601String(),
            ], self::WINDOW_SECONDS);

            // Track blocked IP in index for admin listing
            $index = Cache::get(self::INDEX_KEY, []);
            $index[$ip] = true;
            Cache::put(self::INDEX_KEY, $index, self::WINDOW_SECONDS);
        }
    }

    public function isBlocked(string $ip): bool
    {
        return Cache::has(self::BLOCKED_PREFIX . $ip);
    }

    public function getAttemptCount(string $ip): int
    {
        return (int) Cache::get(self::PREFIX . $ip, 0);
    }

    public function unblock(string $ip): void
    {
        Cache::forget(self::PREFIX . $ip);
        Cache::forget(self::BLOCKED_PREFIX . $ip);

        $index = Cache::get(self::INDEX_KEY, []);
        unset($index[$ip]);
        Cache::put(self::INDEX_KEY, $index, self::WINDOW_SECONDS);
    }

    public function getBlockedIps(): array
    {
        $index = Cache::get(self::INDEX_KEY, []);
        $blocked = [];

        foreach (array_keys($index) as $ip) {
            $data = Cache::get(self::BLOCKED_PREFIX . $ip);
            if ($data) {
                $blocked[] = [
                    'ip' => $ip,
                    'attempts' => $data['attempts'],
                    'blocked_at' => $data['blocked_at'],
                    'expires_at' => $data['expires_at'],
                ];
            }
        }

        return $blocked;
    }
}
