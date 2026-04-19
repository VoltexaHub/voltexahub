<?php
namespace App\Auth\Services;

use Illuminate\Support\Facades\Http;

class TurnstileService
{
    public function verify(string $token, string $ip): bool
    {
        if (app()->environment('testing')) return true;

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret'),
            'response' => $token,
            'remoteip' => $ip,
        ]);

        return $response->json('success') === true;
    }
}
