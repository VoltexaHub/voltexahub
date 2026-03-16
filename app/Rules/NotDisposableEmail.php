<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDisposableEmail implements ValidationRule
{
    protected array $blocklist = [
        'mailinator.com',
        'guerrillamail.com',
        'guerrillamail.info',
        'guerrillamail.biz',
        'guerrillamail.de',
        'guerrillamail.net',
        'guerrillamail.org',
        'throwam.com',
        'throwam.net',
        'yopmail.com',
        'yopmail.fr',
        'cool.fr.nf',
        'jetable.fr.nf',
        'nospam.ze.tc',
        'nomail.xl.cx',
        'mega.zik.dj',
        'speed.1s.fr',
        'courriel.fr.nf',
        'moncourrier.fr.nf',
        'monemail.fr.nf',
        'monmail.fr.nf',
        'dispostable.com',
        'mailnull.com',
        'spamgourmet.com',
        'trashmail.at',
        'trashmail.io',
        'trashmail.me',
        'trashmail.net',
        'trashmail.org',
        'sharklasers.com',
        'guerrillamailblock.com',
        'grr.la',
        'spam4.me',
        'trbvm.com',
        'tempr.email',
        'discard.email',
        'mailtemp.net',
        'tempmail.com',
        'tempmail.net',
        'temp-mail.org',
        'fakeinbox.com',
        'mailnesia.com',
        'maildrop.cc',
        'spamspot.com',
        'spamtrap.ro',
        '10minutemail.com',
        '10minutemail.net',
        '20minutemail.com',
        'mytemp.email',
        'tempinbox.com',
        'airmail.cc',
        'getairmail.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = strtolower(substr(strrchr($value, '@'), 1));

        foreach ($this->blocklist as $blocked) {
            if ($domain === $blocked || str_ends_with($domain, '.' . $blocked)) {
                $fail('Disposable email addresses are not allowed.');
                return;
            }
        }
    }
}
