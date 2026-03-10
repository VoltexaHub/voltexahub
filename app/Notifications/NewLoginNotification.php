<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $ip,
        protected string $time,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New login to your account')
            ->line("A new login was detected from IP {$this->ip} at {$this->time}. If this was not you, please change your password immediately.");
    }
}
