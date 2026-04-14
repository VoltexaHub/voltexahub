<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class UserMentioned extends Notification
{
    use Queueable;

    public function __construct(public Post $post, public User $mentioner) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        if ($notifiable->notify_reply_email ?? true) $channels[] = 'mail';
        if ($notifiable->notify_reply_app ?? true)   $channels[] = 'database';

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $thread = $this->post->thread;
        $forum = $thread->forum;
        $url = route('threads.show', [$forum->slug, $thread->slug]).'#post-'.$this->post->id;

        return (new MailMessage)
            ->subject("{$this->mentioner->name} mentioned you in {$thread->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->mentioner->name} mentioned you in the thread \"{$thread->title}\".")
            ->line('> '.Str::limit(strip_tags($this->post->body), 200))
            ->action('View post', $url);
    }

    public function toArray(object $notifiable): array
    {
        $thread = $this->post->thread;
        $forum = $thread->forum;

        return [
            'type' => 'user_mentioned',
            'post_id' => $this->post->id,
            'thread_id' => $thread->id,
            'thread_title' => $thread->title,
            'forum_slug' => $forum->slug,
            'thread_slug' => $thread->slug,
            'mentioner_id' => $this->mentioner->id,
            'mentioner_name' => $this->mentioner->name,
            'excerpt' => Str::limit(strip_tags($this->post->body), 140),
        ];
    }
}
