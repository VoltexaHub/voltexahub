<?php

namespace App\Observers;

use App\Models\ForumConfig;
use App\Models\Thread;
use App\Services\XpService;

class ThreadObserver
{
    public function created(Thread $thread): void
    {
        $amount = (int) ForumConfig::get('xp_thread_created', 20);
        if ($amount > 0 && $thread->user) {
            XpService::award($thread->user, $amount);
        }
    }
}
