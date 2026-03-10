<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminSecurityAlert;
use App\Mail\MfaEmailOtp;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class MfaController extends Controller
{
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $google2fa = new Google2FA();

        $secret = $google2fa->generateSecretKey();
        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->save();

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json(['message' => 'MFA not initiated. Call enable first.'], 422);
        }

        $google2fa = new Google2FA();
        $secret = Crypt::decryptString($user->two_factor_secret);

        if (!$google2fa->verifyKey($secret, $request->code)) {
            return response()->json(['message' => 'Invalid code.'], 422);
        }

        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(10))->all();

        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = json_encode(
            array_map(fn ($code) => bcrypt($code), $recoveryCodes)
        );
        $user->save();

        AuditLog::log('mfa.enabled', $user);

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid password.'], 422);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        AuditLog::log('mfa.disabled', $user);

        if ($user->is_staff || $user->hasRole(['admin', 'super-admin'])) {
            $ip = $request->header('CF-Connecting-IP') ?? $request->header('X-Forwarded-For') ?? $request->ip();
            $location = null;
            Mail::to($user->email)->send(new AdminSecurityAlert('mfa.disabled', $ip, $location, now()));
        }

        return response()->json(['message' => 'MFA disabled.']);
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid password.'], 422);
        }

        if (!$user->two_factor_confirmed_at) {
            return response()->json(['message' => 'MFA is not enabled.'], 422);
        }

        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(10))->all();

        $user->two_factor_recovery_codes = json_encode(
            array_map(fn ($code) => bcrypt($code), $recoveryCodes)
        );
        $user->save();

        AuditLog::log('mfa.recovery_codes_regenerated', $user);

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function sendEmailOtp(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
        ]);

        $pending = Cache::get("mfa.pending.{$request->temp_token}");

        if (!$pending) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        $user = User::find($pending['user_id']);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 422);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("mfa.email_otp.{$user->id}", $code, 600);

        Mail::to($user->email)->send(new MfaEmailOtp($code));

        return response()->json(['message' => 'Code sent.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
            'code' => ['required', 'string'],
            'type' => ['required', 'string', 'in:totp,email,recovery'],
        ]);

        $pending = Cache::get("mfa.pending.{$request->temp_token}");

        if (!$pending) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        $user = User::find($pending['user_id']);

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        $valid = false;

        switch ($request->type) {
            case 'totp':
                $google2fa = new Google2FA();
                $secret = Crypt::decryptString($user->two_factor_secret);
                $valid = $google2fa->verifyKey($secret, $request->code, 1);
                break;

            case 'email':
                $cachedCode = Cache::get("mfa.email_otp.{$user->id}");
                if ($cachedCode && $cachedCode === $request->code) {
                    $valid = true;
                    Cache::forget("mfa.email_otp.{$user->id}");
                }
                break;

            case 'recovery':
                $storedCodes = json_decode($user->two_factor_recovery_codes, true) ?? [];
                foreach ($storedCodes as $index => $hashedCode) {
                    if (Hash::check($request->code, $hashedCode)) {
                        $valid = true;
                        unset($storedCodes[$index]);
                        $user->two_factor_recovery_codes = json_encode(array_values($storedCodes));
                        $user->save();
                        break;
                    }
                }
                break;
        }

        if (!$valid) {
            return response()->json(['message' => 'Invalid code.'], 422);
        }

        Cache::forget("mfa.pending.{$request->temp_token}");

        $user->update(['is_online' => true, 'last_active_at' => now()]);

        $tokenResult = $user->createToken('auth-token');
        $token = $tokenResult->plainTextToken;

        $tokenResult->accessToken->update([
            'ip_address' => $pending['ip'] ?? null,
            'location'   => $pending['location'] ?? null,
        ]);

        AuditLog::log('mfa.verified', $user, ['type' => $request->type]);

        return response()->json([
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token,
            ],
            'message' => 'Login successful.',
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'enabled' => $user->two_factor_confirmed_at !== null,
            'confirmed_at' => $user->two_factor_confirmed_at,
        ]);
    }
}
