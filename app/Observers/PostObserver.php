<?php

namespace App\Observers;

use App\Models\ForumConfig;
use App\Models\Post;
use App\Services\XpService;

class PostObserver
{
    public function created(Post $post): void
    {
        $amount = (int) ForumConfig::get('xp_post_created', 10);
        if ($amount > 0 && $post->user) {
            XpService::award($post->user, $amount);
        }
    }
}
