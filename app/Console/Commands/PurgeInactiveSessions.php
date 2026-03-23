<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class PurgeInactiveSessions extends Command
{
    protected $signature = 'sessions:purge-inactive';

    protected $description = 'Purge Sanctum tokens that are unused or older than 30 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(30);

        $purged = PersonalAccessToken::where(function ($query) use ($cutoff) {
            $query->whereNull('last_used_at')
                  ->orWhere('last_used_at', '<', $cutoff);
        })->delete();

        Cache::put('sessions:last_purge', now()->toIso8601String());

        Log::info("PurgeInactiveSessions: purged {$purged} stale tokens.");
        $this->info("Purged {$purged} inactive session(s).");

        return 0;
    }
}
