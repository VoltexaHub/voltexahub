<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdminSecurityAlert extends Mailable
{
    private const SUBJECTS = [
        'login.success'          => 'Admin login: successful',
        'login.failure'          => 'Admin login: failed attempt',
        'password.changed'       => 'Admin password changed',
        'password.change_failed' => 'Admin password change attempt failed',
        'email.change_requested' => 'Admin email change requested',
        'mfa.disabled'           => 'Admin MFA disabled',
    ];

    public function __construct(
        public string $event,
        public string $ip,
        public ?string $location,
        public Carbon $time,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECTS[$this->event] ?? 'Admin security alert',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_security_alert',
            with: [
                'event'    => $this->event,
                'ip'       => $this->ip,
                'location' => $this->location,
                'time'     => $this->time->toDateTimeString(),
                'subject'  => self::SUBJECTS[$this->event] ?? 'Admin security alert',
            ],
        );
    }
}
