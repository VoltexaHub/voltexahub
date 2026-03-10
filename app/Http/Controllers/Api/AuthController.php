<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\NewLoginNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users', 'alpha_dash'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->assignRole('member');
        $user->checkAchievements();

        // Send verification email
        event(new Registered($user));

        // Send welcome email
        Mail::to($user)->send(new WelcomeEmail($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token,
            ],
            'message' => 'Registration successful. Please check your email to verify your account.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $lockoutKey = 'auth.lockout.' . md5(strtolower($validated['email']));
        $attempts = Cache::get($lockoutKey, 0);

        if ($attempts >= 10) {
            AuditLog::log('login.lockout', null, ['email' => $validated['email']]);

            return response()->json([
                'message' => 'Too many failed login attempts. Please try again later.',
            ], 429);
        }

        if (! Auth::attempt($validated)) {
            Cache::put($lockoutKey, $attempts + 1, 900);

            AuditLog::log('login.failure', null, ['email' => $validated['email']]);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Clear lockout on success
        Cache::forget($lockoutKey);

        $user = User::where('email', $validated['email'])->first();
        $user->update(['is_online' => true, 'last_active_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        AuditLog::log('login.success', $user);

        // Login notification for new IPs
        $ip = $request->ip();
        $knownIps = $user->known_ips ?? [];

        if (!in_array($ip, $knownIps)) {
            $user->notify(new NewLoginNotification($ip, now()->toDateTimeString()));

            $knownIps[] = $ip;
            $user->known_ips = array_slice($knownIps, -10);
            $user->save();
        }

        return response()->json([
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token,
            ],
            'message' => 'Login successful.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        $request->user()->update(['is_online' => false]);

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink(['email' => $request->email]);

        // Always return success to prevent email enumeration
        return response()->json([
            'message' => 'If an account with that email exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        // Log the user in and return a new token
        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('auth-token')->plainTextToken;

        AuditLog::log('password.reset', $user);

        return response()->json([
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token,
            ],
            'message' => 'Password has been reset successfully.',
        ]);
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json([
                'message' => 'Invalid verification link.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $currentTokenId = $currentToken?->id ?? null;

        $tokens = $user->tokens()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'is_current' => $currentTokenId !== null && $token->id === $currentTokenId,
            ]);

        return response()->json(['data' => $tokens]);
    }

    public function destroySession(Request $request, int $tokenId): JsonResponse
    {
        $user = $request->user();
        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        if ($token->id === $user->currentAccessToken()->id) {
            return response()->json(['message' => 'Cannot revoke current session. Use logout instead.'], 422);
        }

        $token->delete();

        return response()->json(['message' => 'Session revoked.']);
    }

    public function destroyAllSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()->id;

        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json(['message' => 'All other sessions revoked.']);
    }
}
