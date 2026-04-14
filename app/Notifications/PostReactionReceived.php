<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostReactionReceived extends Notification
{
    use Queueable;

    public function __construct(
        public Post $post,
        public User $reactor,
        public string $emoji,
    ) {}

    public function via(object $notifiable): array
    {
        // In-app only; we deliberately never email for reactions to avoid noise.
        return ($notifiable->notify_reply_app ?? true) ? ['database'] : [];
    }

    public function databaseType(object $notifiable): string
    {
        return 'post_reaction';
    }

    public function toArray(object $notifiable): array
    {
        $thread = $this->post->thread;
        $forum = $thread?->forum;

        return [
            'type' => 'post_reaction',
            'emoji' => $this->emoji,
            'post_id' => $this->post->id,
            'thread_id' => $thread?->id,
            'thread_title' => $thread?->title,
            'forum_slug' => $forum?->slug,
            'thread_slug' => $thread?->slug,
            'reactor_id' => $this->reactor->id,
            'reactor_name' => $this->reactor->name,
        ];
    }
}
