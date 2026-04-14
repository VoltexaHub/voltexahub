<?php

namespace App\Support;

use App\Models\User;

/**
 * Utilities for discovering and rendering @username mentions.
 *
 * Match rule: "@" followed by 2-40 characters drawn from letters, digits, dot,
 * dash, and underscore. Usernames with spaces are NOT mentionable by design —
 * add a handle column if you need that.
 */
class Mentions
{
    public const PATTERN = '/(?<![\w@])@([A-Za-z0-9][A-Za-z0-9._\-]{1,39})/u';

    /** Returns the user ids referenced by unique @name tokens in the text. */
    public static function extractUserIds(string $text): array
    {
        if (! preg_match_all(self::PATTERN, $text, $m)) {
            return [];
        }
        $names = array_unique($m[1]);
        if (empty($names)) return [];

        return User::query()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->values()
            ->all();
    }
}
