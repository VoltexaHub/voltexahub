<?php

namespace App\Rules;

use App\Models\ForumConfig;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDisposableEmail implements ValidationRule
{
    protected static ?array $cachedBlocklist = null;

    protected static function getBlocklist(): array
    {
        if (static::$cachedBlocklist !== null) {
            return static::$cachedBlocklist;
        }

        $raw = ForumConfig::get("email_blocklist", "");
        if (empty(trim($raw))) {
            static::$cachedBlocklist = [];
            return [];
        }

        static::$cachedBlocklist = array_filter(
            array_map("trim", explode("\n", strtolower($raw))),
            fn($d) => !empty($d) && !str_starts_with($d, "#")
        );

        return static::$cachedBlocklist;
    }

    public static function clearCache(): void
    {
        static::$cachedBlocklist = null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = strtolower(substr(strrchr($value, "@"), 1));
        $blocklist = static::getBlocklist();

        foreach ($blocklist as $blocked) {
            if ($domain === $blocked || str_ends_with($domain, "." . $blocked)) {
                $fail("Disposable email addresses are not allowed.");
                return;
            }
        }
    }
}
