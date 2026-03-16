<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminSecurityAlert;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use App\Plugins\PluginHook;
use App\Rules\NotDisposableEmail;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Rate limiting
        $key = "register_attempts:" . $request->ip();
        $attempts = Cache::get($key, 0);
        if ($attempts >= 3) {
            return response()->json([
                "message" => "Too many registration attempts. Please try again later.",
                "errors" => ["register" => ["Too many attempts from this IP. Please wait before trying again."]]
            ], 429);
        }
        Cache::put($key, $attempts + 1, now()->addHour());

        // Honeypot check
        if ($request->filled("website")) {
            return response()->json(["message" => "Registration failed."], 422);
        }

        // Turnstile verification
        $turnstileToken = $request->input("cf_turnstile_response");
        $turnstileSecret = config("turnstile.secret");
        if (!empty($turnstileSecret)) {
            if (empty($turnstileToken)) {
                return response()->json([
                    "message" => "CAPTCHA verification required.",
                    "errors" => ["captcha" => ["Please complete the CAPTCHA verification."]]
                ], 422);
            }
            $turnstileResponse = Http::asForm()->post(config("turnstile.verify_url"), [
                "secret" => $turnstileSecret,
                "response" => $turnstileToken,
                "remoteip" => $request->ip(),
            ]);
            if (!$turnstileResponse->successful() || !$turnstileResponse->json("success")) {
                return response()->json([
                    "message" => "CAPTCHA verification failed.",
                    "errors" => ["captcha" => ["CAPTCHA verification failed. Please try again."]]
                ], 422);
            }
        }

        try {
            $validated = $request->validate([
                'username' => ['required', 'string', 'max:255', 'unique:users', 'alpha_dash'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users', new NotDisposableEmail()],
                'password' => ['required', 'confirmed', PasswordRule::defaults()],
            ]);
        } catch (ValidationException $e) {
            $errorKeys = array_keys($e->errors());
            $hasEnumFields = array_intersect($errorKeys, ['username', 'email']);

            if (!empty($hasEnumFields)) {
                // Check if the errors are specifically unique constraint failures
                $isUniqueFailure = false;
                foreach ($hasEnumFields as $field) {
                    foreach ($e->errors()[$field] as $msg) {
                        if (str_contains($msg, 'already been taken')) {
                            $isUniqueFailure = true;
                            break 2;
                        }
                    }
                }

                if ($isUniqueFailure) {
                    // Remove unique-related errors and replace with generic message
                    $remainingErrors = [];
                    foreach ($e->errors() as $field => $messages) {
                        if (in_array($field, ['username', 'email'])) {
                            $filtered = array_filter($messages, fn($m) => !str_contains($m, 'already been taken'));
                            if (!empty($filtered)) {
                                $remainingErrors[$field] = array_values($filtered);
                            }
                        } else {
                            $remainingErrors[$field] = $messages;
                        }
                    }

                    $remainingErrors['account'] = ['An account with those details already exists.'];

                    throw ValidationException::withMessages($remainingErrors);
                }
            }

            throw $e;
        }

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

        try {
            PluginHook::fire('user.registered', $user);
        } catch (\Throwable) {}

        $token = $user->createToken('auth-token')->plainTextToken;

        Cache::forget("register_attempts:" . $request->ip());

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

            // Admin failed login alert
            $failedUser = User::where('email', $validated['email'])->first();
            if ($failedUser && ($failedUser->is_staff || $failedUser->hasRole(['admin', 'super-admin']))) {
                $ip = $this->getRealIp($request);
                $location = $this->resolveLocation($ip);
                AuditLog::log('admin.login.failure', $failedUser, ['ip' => $ip, 'location' => $location]);
                Mail::to($failedUser->email)->send(new AdminSecurityAlert('login.failure', $ip, $location, now()));
            }

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Clear lockout on success
        Cache::forget($lockoutKey);

        $user = User::where('email', $validated['email'])->first();
        $ip = $this->getRealIp($request);
        $location = $this->resolveLocation($ip);

        // MFA check: if user has confirmed MFA, redirect to MFA verification
        if ($user->two_factor_confirmed_at) {
            $tempToken = Str::uuid()->toString();
            Cache::put("mfa.pending.{$tempToken}", [
                'user_id'  => $user->id,
                'ip'       => $ip,
                'location' => $location,
            ], 300);

            return response()->json([
                'requires_mfa' => true,
                'temp_token' => $tempToken,
                'has_totp' => true,
                'has_email' => true,
            ]);
        }

        $user->update(['is_online' => true, 'last_active_at' => now()]);

        try {
            PluginHook::fire('user.login', $user);
        } catch (\Throwable) {}

        $tokenResult = $user->createToken('auth-token');
        $token = $tokenResult->plainTextToken;

        // Store IP + location on the token
        $tokenResult->accessToken->update([
            'ip_address' => $ip,
            'location'   => $location,
        ]);

        AuditLog::log('login.success', $user);

        // Admin login alert (every login)
        if ($user->is_staff || $user->hasRole(['admin', 'super-admin'])) {
            AuditLog::log('admin.login.success', $user, ['ip' => $ip, 'location' => $location]);
            Mail::to($user->email)->send(new AdminSecurityAlert('login.success', $ip, $location, now()));
        }

        // Login notification for new IPs
        $knownIps = $user->known_ips ?? [];

        if (!in_array($ip, $knownIps)) {
            // Admin/staff get AdminSecurityAlert on every login — skip the duplicate new-IP email
            if (!$user->is_staff && !$user->hasRole(['admin', 'super-admin'])) {
                $user->notify(new NewLoginNotification($ip, now()->toDateTimeString()));
            }

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

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://community.voltexahub.com'), '/');
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect("{$frontendUrl}/verify-email?status=error&message=Invalid+verification+link.");
        }

        if ($user->hasVerifiedEmail()) {
            return redirect("{$frontendUrl}/verify-email?status=already_verified");
        }

        $user->markEmailAsVerified();

        return redirect("{$frontendUrl}/verify-email?status=success");
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

    public function confirmEmailChange(Request $request): JsonResponse
    {
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired link.'], 403);
        }

        $user = User::findOrFail($request->query('user'));

        if (!$user->pending_email) {
            return response()->json(['message' => 'No pending email change.'], 422);
        }

        $user->email = $user->pending_email;
        $user->pending_email = null;
        $user->save();

        return response()->json(['message' => 'Email address updated successfully.']);
    }

    private function getRealIp(Request $request): string
    {
        // Cloudflare passes the real client IP in CF-Connecting-IP
        return $request->header('CF-Connecting-IP')
            ?? $request->header('X-Forwarded-For')
            ?? $request->ip();
    }

    private function resolveLocation(string $ip): ?string
    {
        // Skip private/loopback IPs
        if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'Local';
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->get("http://ip-api.com/json/{$ip}?fields=city,regionName,country,status");

            if ($response->ok()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    $parts = array_filter([$data['city'] ?? null, $data['regionName'] ?? null, $data['country'] ?? null]);
                    return implode(', ', array_unique($parts)) ?: null;
                }
            }
        } catch (\Throwable) {
            // GeoIP lookup failed — return null silently
        }

        return null;
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
                'ip_address' => $token->ip_address,
                'location' => $token->location,
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
