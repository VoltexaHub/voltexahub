<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GithubSponsor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitHubSponsorsController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = config('services.github_sponsors.webhook_secret');

        if (! $secret) {
            return response()->json(['message' => 'Webhook secret not configured.'], 500);
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return response()->json(['message' => 'Missing signature.'], 403);
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $payload = $request->all();
        $action = $payload['action'] ?? null;
        $sponsorship = $payload['sponsorship'] ?? [];
        $sponsor = $sponsorship['sponsor'] ?? [];
        $githubLogin = $sponsor['login'] ?? null;
        $tierName = $sponsorship['tier']['name'] ?? null;

        if (! $githubLogin) {
            return response()->json(['message' => 'No sponsor login found.'], 422);
        }

        match ($action) {
            'created' => $this->handleCreated($githubLogin, $tierName),
            'cancelled' => $this->handleCancelled($githubLogin),
            'tier_changed' => $this->handleTierChanged($githubLogin, $tierName),
            default => null,
        };

        return response()->json(['message' => 'OK']);
    }

    protected function handleCreated(string $githubLogin, ?string $tier): void
    {
        GithubSponsor::updateOrCreate(
            ['github_login' => $githubLogin],
            [
                'tier' => $tier,
                'active' => true,
                'sponsored_at' => now(),
                'cancelled_at' => null,
            ]
        );

        $this->syncUserSponsorStatus($githubLogin, true, $tier);
    }

    protected function handleCancelled(string $githubLogin): void
    {
        GithubSponsor::where('github_login', $githubLogin)->update([
            'active' => false,
            'cancelled_at' => now(),
        ]);

        $this->syncUserSponsorStatus($githubLogin, false);
    }

    protected function handleTierChanged(string $githubLogin, ?string $tier): void
    {
        GithubSponsor::where('github_login', $githubLogin)->update([
            'tier' => $tier,
        ]);

        User::where('github_username', $githubLogin)->update([
            'sponsor_tier' => $tier,
        ]);
    }

    protected function syncUserSponsorStatus(string $githubLogin, bool $isSponsor, ?string $tier = null): void
    {
        $update = ['is_sponsor' => $isSponsor];

        if ($isSponsor) {
            $update['sponsor_since'] = now();
            $update['sponsor_tier'] = $tier;
        }

        User::where('github_username', $githubLogin)->update($update);
    }
}
