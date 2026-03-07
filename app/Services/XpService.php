<?php

namespace App\Services;

use App\Models\Level;
use App\Models\User;

class XpService
{
    public static function award(User $user, int $amount): void
    {
        $user->increment('xp', $amount);
    }

    public static function levelFor(int $xp): ?Level
    {
        return Level::where('xp_required', '<=', $xp)
            ->orderByDesc('xp_required')
            ->first();
    }

    public static function nextLevel(int $xp): ?Level
    {
        return Level::where('xp_required', '>', $xp)
            ->orderBy('xp_required')
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
