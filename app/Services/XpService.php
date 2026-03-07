<?php

namespace App\Services;

use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class XpService
{
    private static function getLevels(): \Illuminate\Support\Collection
    {
        return Cache::remember('forum_levels', 300, function () {
            return Level::orderBy('level')->get();
        });
    }

    public static function award(User $user, int $amount): void
    {
        $boost = \App\Models\UserXpBoost::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->orderByDesc('expires_at')
            ->first();

        if ($boost) {
            $amount = (int) round($amount * $boost->multiplier);
        }

        $user->increment('xp', $amount);
    }

    public static function levelFor(int $xp): ?Level
    {
        return self::getLevels()
            ->where('xp_required', '<=', $xp)
            ->sortByDesc('xp_required')
            ->first();
    }

    public static function nextLevel(int $xp): ?Level
    {
        return self::getLevels()
            ->where('xp_required', '>', $xp)
            ->sortBy('xp_required')
            ->first();
    }

    public static function progressPercent(int $xp): int
    {
        $current = static::levelFor($xp);
        $next = static::nextLevel($xp);

        if (!$current || !$next) {
            return $current && !$next ? 100 : 0;
        }

        $range = $next->xp_required - $current->xp_required;
        if ($range <= 0) return 100;

        $progress = $xp - $current->xp_required;
        return (int) min(100, max(0, ($progress / $range) * 100));
    }
}
