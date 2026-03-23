<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use App\Services\BruteForceProtection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;

class AdminSecurityController extends Controller
{
    public function getSettings(): JsonResponse
    {
        $value = ForumConfig::get('admin_reauth_required', 'true');

        return response()->json([
            'admin_reauth_required' => $value === 'true' || $value === true,
            'turnstile_site' => ForumConfig::get('turnstile_site', ''),
            'turnstile_secret_key' => '',
            'email_blocklist' => ForumConfig::get('email_blocklist', ''),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'admin_reauth_required' => 'required|boolean',
            'turnstile_site' => 'nullable|string|max:255',
            'turnstile_secret_key' => 'nullable|string|max:255',
            'email_blocklist' => 'nullable|string',
        ]);

        ForumConfig::set('admin_reauth_required', $request->boolean('admin_reauth_required') ? 'true' : 'false');
        ForumConfig::set('turnstile_site', $request->input('turnstile_site', ''));

        if ($request->filled('turnstile_secret_key')) {
            ForumConfig::set('turnstile_secret_key', $request->input('turnstile_secret_key'));
        }

        ForumConfig::set('email_blocklist', $request->input('email_blocklist', ''));
        \App\Rules\NotDisposableEmail::clearCache();

        return response()->json([
            'message' => 'Security settings updated.',
            'admin_reauth_required' => $request->boolean('admin_reauth_required'),
            'turnstile_site' => $request->input('turnstile_site', ''),
            'turnstile_secret_key' => '',
            'email_blocklist' => $request->input('email_blocklist', ''),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $reauthRequired = ForumConfig::get('admin_reauth_required', 'true');

        if ($reauthRequired === 'false' || $reauthRequired === false) {
            return $this->issueReauthToken($request->user());
        }

        $user = $request->user();
        $password = $request->input('password');
        $mfaCode = $request->input('mfa_code');

        if (!$password && !$mfaCode) {
            return response()->json([
                'message' => 'Please provide a password or MFA code.',
            ], 422);
        }

        if ($password) {
            if (Hash::check($password, $user->password)) {
                return $this->issueReauthToken($user);
            }

            return response()->json(['verified' => false, 'message' => 'Incorrect password.'], 422);
        }

        if ($mfaCode) {
            if (!$user->two_factor_confirmed_at) {
                return response()->json([
                    'message' => 'MFA is not enabled for your account.',
                ], 422);
            }

            $google2fa = new Google2FA();

            if ($google2fa->verifyKey($user->two_factor_secret, $mfaCode)) {
                return $this->issueReauthToken($user);
            }

            return response()->json(['verified' => false, 'message' => 'Incorrect MFA code.'], 422);
        }

        return response()->json(['verified' => false], 422);
    }

    public function sessionsStats(): JsonResponse
    {
        $cutoff = now()->subDays(30);

        $total = PersonalAccessToken::count();
        $stale = PersonalAccessToken::where(function ($query) use ($cutoff) {
            $query->whereNull('last_used_at')
                  ->orWhere('last_used_at', '<', $cutoff);
        })->count();

        return response()->json([
            'data' => [
                'total_active' => $total,
                'stale' => $stale,
                'last_purge' => Cache::get('sessions:last_purge'),
            ],
        ]);
    }

    public function blockedIps(BruteForceProtection $bf): JsonResponse
    {
        return response()->json([
            'data' => $bf->getBlockedIps(),
        ]);
    }

    public function unblockIp(BruteForceProtection $bf, string $ip): JsonResponse
    {
        $bf->unblock($ip);

        return response()->json([
            'message' => "IP {$ip} has been unblocked.",
        ]);
    }

    private function issueReauthToken($user): JsonResponse
    {
        $token = Str::uuid()->toString();
        $ttl = 300; // 5 minutes

        Cache::put("reauth:{$user->id}:{$token}", true, $ttl);

        return response()->json([
            'verified' => true,
            'token' => $token,
            'expires_in' => $ttl,
        ]);
    }
}
