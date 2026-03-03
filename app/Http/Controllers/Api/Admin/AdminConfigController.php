<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminConfigController extends Controller
{
    public function index(): JsonResponse
    {
        $configs = ForumConfig::all()->pluck('value', 'key');

        $mailKeys = ['mail_mailer','mail_host','mail_port','mail_username','mail_password','mail_encryption','mail_from_address','mail_from_name'];

        return response()->json([
            'data' => array_merge($configs->toArray(), [
                'mail' => collect($mailKeys)->mapWithKeys(fn ($k) => [$k => $configs[$k] ?? ''])->toArray(),
            ]),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'config' => ['required', 'array'],
            'config.*' => ['nullable'],
        ]);

        foreach ($validated['config'] as $key => $value) {
            ForumConfig::set($key, is_bool($value) ? ($value ? 'true' : 'false') : (string)($value ?? ''));
        }

        $configs = ForumConfig::all()->pluck('value', 'key');

        return response()->json([
            'data' => $configs,
            'message' => 'Configuration updated successfully.',
        ]);
    }
}
    public function testEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "This is a test email from VoltexaHub.\n\nIf you received this, your email settings are working correctly!",
                function ($message) use ($user) {
                    $message->to($user->email, $user->username)
                            ->subject('VoltexaHub — Test Email');
                }
            );

            return response()->json(['message' => 'Test email sent to ' . $user->email]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 422);
        }
    }
}