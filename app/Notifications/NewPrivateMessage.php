<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewPrivateMessage extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $author = $this->message->author?->name ?? 'Someone';

        return (new MailMessage)
            ->subject("New message from {$author}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$author} sent you a message.")
            ->line('> '.Str::limit(strip_tags($this->message->body), 200))
            ->action('Read message', route('messages.show', $this->message->conversation_id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'private_message',
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'author_id' => $this->message->user_id,
            'author_name' => $this->message->author?->name,
            'excerpt' => Str::limit(strip_tags($this->message->body), 140),
        ];
    }
}
