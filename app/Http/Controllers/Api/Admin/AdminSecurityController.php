<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'admin_reauth_required' => 'required|boolean',
            'turnstile_site' => 'nullable|string|max:255',
            'turnstile_secret_key' => 'nullable|string|max:255',
        ]);

        ForumConfig::set('admin_reauth_required', $request->boolean('admin_reauth_required') ? 'true' : 'false');
        ForumConfig::set('turnstile_site', $request->input('turnstile_site', ''));

        if ($request->filled('turnstile_secret_key')) {
            ForumConfig::set('turnstile_secret_key', $request->input('turnstile_secret_key'));
        }

        return response()->json([
            'message' => 'Security settings updated.',
            'admin_reauth_required' => $request->boolean('admin_reauth_required'),
            'turnstile_site' => $request->input('turnstile_site', ''),
            'turnstile_secret_key' => '',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $reauthRequired = ForumConfig::get('admin_reauth_required', 'true');

        if ($reauthRequired === 'false' || $reauthRequired === false) {
            return response()->json(['verified' => true]);
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
                return response()->json(['verified' => true]);
            }

            return response()->json(['verified' => false, 'message' => 'Incorrect password.']);
        }

        if ($mfaCode) {
            if (!$user->two_factor_confirmed_at) {
                return response()->json([
                    'message' => 'MFA is not enabled for your account.',
                ], 422);
            }

            $google2fa = new Google2FA();

            if ($google2fa->verifyKey($user->two_factor_secret, $mfaCode)) {
                return response()->json(['verified' => true]);
            }

            return response()->json(['verified' => false, 'message' => 'Incorrect MFA code.']);
        }

        return response()->json(['verified' => false], 422);
    }
}
