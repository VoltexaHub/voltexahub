<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingEmailChange extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $signedUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm Email Change — ' . \App\Models\ForumConfig::get('forum_name', 'our community'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pending_email_change',
            with: [
                'username' => $this->user->username,
                'newEmail' => $this->user->pending_email,
                'confirmUrl' => $this->signedUrl,
                'forumName' => \App\Models\ForumConfig::get('forum_name', 'Community Forums'),
            ],
        );
    }
}
