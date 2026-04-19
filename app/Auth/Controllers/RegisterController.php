<?php
namespace App\Auth\Controllers;

use App\Auth\Services\TurnstileService;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisterController
{
    public function show()
    {
        return Inertia::render('Auth/Register', [
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(Request $request, TurnstileService $turnstile)
    {
        if (!$turnstile->verify($request->input('_turnstile', ''), $request->ip())) {
            return back()->withErrors(['_turnstile' => 'Captcha verification failed.']);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'unique:users', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'email'    => ['required', 'email', 'max:255', 'unique:users', 'lowercase'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'referral_code' => Str::upper(Str::random(8)),
        ]);

        Auth::login($user);
        event(new Registered($user));
        $request->session()->regenerate();

        return redirect()->route('forum.index');
    }
}
