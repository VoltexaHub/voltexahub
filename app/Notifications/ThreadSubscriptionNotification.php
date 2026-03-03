<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ThreadSubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Thread $thread,
        public Post $post,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'thread_reply',
            'title' => 'New reply in subscribed thread',
            'body' => $this->post->user->username . ' replied to "' . $this->thread->title . '"',
            'url' => '/threads/' . $this->thread->id,
            'icon' => 'message-square',
            'created_at' => now()->toISOString(),
        ];
    }
}
