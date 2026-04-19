<?php
namespace App\Auth\Controllers;

use App\Auth\Services\TurnstileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController
{
    public function show()
    {
        return Inertia::render('Auth/Login', [
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(Request $request, TurnstileService $turnstile)
    {
        if (!$turnstile->verify($request->input('_turnstile', ''), $request->ip())) {
            return back()->withErrors(['_turnstile' => 'Captcha verification failed.']);
        }

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->intended(route('forum.index'));
    }
}
