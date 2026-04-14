<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewThreadReply extends Notification
{
    use Queueable;

    public function __construct(public Post $post) {}

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
        $author = $this->post->author?->name ?? 'Someone';

        return (new MailMessage)
            ->subject('New reply in: '.$thread->title)
            ->greeting("Hi {$notifiable->name},")
            ->line("{$author} replied in the thread \"{$thread->title}\".")
            ->line('> '.Str::limit(strip_tags($this->post->body), 200))
            ->action('View reply', $url)
            ->line('You can mute this thread by visiting it and turning off notifications (coming soon).');
    }

    public function toArray(object $notifiable): array
    {
        $thread = $this->post->thread;
        $forum = $thread->forum;

        return [
            'type' => 'thread_reply',
            'post_id' => $this->post->id,
            'thread_id' => $thread->id,
            'thread_title' => $thread->title,
            'forum_slug' => $forum->slug,
            'thread_slug' => $thread->slug,
            'author_id' => $this->post->user_id,
            'author_name' => $this->post->author?->name,
            'excerpt' => Str::limit(strip_tags($this->post->body), 140),
        ];
    }
}
