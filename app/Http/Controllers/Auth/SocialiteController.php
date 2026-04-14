<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialiteController extends Controller
{
    private const ALLOWED = ['github', 'google'];

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        $this->assertEnabled($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->assertEnabled($provider);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('flash.error', 'Authentication failed. Please try again.');
        }

        if (! $social->getEmail()) {
            return redirect()->route('login')->with('flash.error', "Your {$provider} account has no public email. Please register manually.");
        }

        $user = User::where('oauth_provider', $provider)
            ->where('oauth_provider_id', $social->getId())
            ->first();

        if (! $user) {
            $user = User::where('email', $social->getEmail())->first();

            if ($user) {
                $user->update([
                    'oauth_provider' => $provider,
                    'oauth_provider_id' => $social->getId(),
                    'oauth_avatar' => $social->getAvatar(),
                ]);
            } else {
                $user = User::create([
                    'name' => $social->getName() ?: Str::before($social->getEmail(), '@'),
                    'email' => $social->getEmail(),
                    'email_verified_at' => now(),
                    'oauth_provider' => $provider,
                    'oauth_provider_id' => $social->getId(),
                    'oauth_avatar' => $social->getAvatar(),
                ]);
            }
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }

    private function assertEnabled(string $provider): void
    {
        abort_unless(in_array($provider, self::ALLOWED, true), 404);
        abort_unless(config("services.{$provider}.client_id") && config("services.{$provider}.client_secret"), 404, 'OAuth provider not configured.');
    }
}
