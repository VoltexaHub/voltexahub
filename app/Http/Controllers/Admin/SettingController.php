<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'oauth' => [
                    'github' => [
                        'client_id' => Setting::get('oauth.github.client_id', ''),
                        'has_secret' => (bool) Setting::get('oauth.github.client_secret'),
                    ],
                    'google' => [
                        'client_id' => Setting::get('oauth.google.client_id', ''),
                        'has_secret' => (bool) Setting::get('oauth.google.client_secret'),
                    ],
                ],
                'announcement' => [
                    'message' => Setting::get('announcement.message', ''),
                    'tone' => Setting::get('announcement.tone', 'info'),
                ],
                'pages' => [
                    'privacy' => [
                        'title' => Setting::get('pages.privacy.title', 'Privacy Policy'),
                        'body' => Setting::get('pages.privacy.body', ''),
                    ],
                    'terms' => [
                        'title' => Setting::get('pages.terms.title', 'Terms of Service'),
                        'body' => Setting::get('pages.terms.body', ''),
                    ],
                ],
            ],
            'callbackUrls' => [
                'github' => url('/auth/github/callback'),
                'google' => url('/auth/google/callback'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'github_client_id' => ['nullable', 'string', 'max:200'],
            'github_client_secret' => ['nullable', 'string', 'max:500'],
            'google_client_id' => ['nullable', 'string', 'max:200'],
            'google_client_secret' => ['nullable', 'string', 'max:500'],
            'announcement_message' => ['nullable', 'string', 'max:1000'],
            'announcement_tone' => ['nullable', 'string', 'in:info,notice,warning'],
            'privacy_title' => ['nullable', 'string', 'max:120'],
            'privacy_body' => ['nullable', 'string', 'max:20000'],
            'terms_title' => ['nullable', 'string', 'max:120'],
            'terms_body' => ['nullable', 'string', 'max:20000'],
        ]);

        Setting::put('oauth.github.client_id', $data['github_client_id'] ?? null);
        if (! empty($data['github_client_secret'])) {
            Setting::put('oauth.github.client_secret', $data['github_client_secret'], encrypted: true);
        }

        Setting::put('oauth.google.client_id', $data['google_client_id'] ?? null);
        if (! empty($data['google_client_secret'])) {
            Setting::put('oauth.google.client_secret', $data['google_client_secret'], encrypted: true);
        }

        $newMessage = trim((string) ($data['announcement_message'] ?? ''));
        $oldMessage = (string) Setting::get('announcement.message', '');
        Setting::put('announcement.message', $newMessage !== '' ? $newMessage : null);
        Setting::put('announcement.tone', $data['announcement_tone'] ?? 'info');
        if ($newMessage !== $oldMessage) {
            $version = (int) Setting::get('announcement.version', 0) + 1;
            Setting::put('announcement.version', (string) $version);
        }

        foreach (['privacy', 'terms'] as $page) {
            if (array_key_exists("{$page}_title", $data)) {
                Setting::put("pages.{$page}.title", $data["{$page}_title"] ?: null);
            }
            if (array_key_exists("{$page}_body", $data)) {
                Setting::put("pages.{$page}.body", $data["{$page}_body"] ?: null);
            }
        }

        return back()->with('flash.success', 'Settings saved.');
    }

    public function clearSecret(Request $request): RedirectResponse
    {
        $provider = $request->validate(['provider' => ['required', 'in:github,google']])['provider'];
        Setting::forget("oauth.{$provider}.client_secret");

        return back()->with('flash.success', ucfirst($provider).' secret cleared.');
    }
}
