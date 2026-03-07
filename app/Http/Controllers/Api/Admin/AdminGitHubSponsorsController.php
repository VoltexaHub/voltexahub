<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GithubSponsor;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminGitHubSponsorsController extends Controller
{
    public function index(): JsonResponse
    {
        $sponsors = GithubSponsor::orderByDesc('created_at')->get()->map(function ($s) {
            $linkedUser = User::where('github_username', $s->github_login)->first();

            return [
                'id' => $s->id,
                'github_login' => $s->github_login,
                'tier' => $s->tier,
                'active' => $s->active,
                'sponsored_at' => $s->sponsored_at?->toISOString(),
                'cancelled_at' => $s->cancelled_at?->toISOString(),
                'linked_user' => $linkedUser ? [
                    'id' => $linkedUser->id,
                    'username' => $linkedUser->username,
                    'is_sponsor' => $linkedUser->is_sponsor,
                ] : null,
                'created_at' => $s->created_at?->toISOString(),
            ];
        });

        return response()->json(['data' => $sponsors]);
    }

    public function grant(int $id): JsonResponse
    {
        $sponsor = GithubSponsor::findOrFail($id);
        $sponsor->update(['active' => true, 'cancelled_at' => null]);

        $user = User::where('github_username', $sponsor->github_login)->first();
        if ($user) {
            $user->update([
                'is_sponsor' => true,
                'sponsor_since' => $user->sponsor_since ?? now(),
                'sponsor_tier' => $sponsor->tier,
            ]);
        }

        return response()->json(['message' => 'Sponsor status granted.']);
    }

    public function revoke(int $id): JsonResponse
    {
        $sponsor = GithubSponsor::findOrFail($id);
        $sponsor->update(['active' => false, 'cancelled_at' => now()]);

        User::where('github_username', $sponsor->github_login)->update([
            'is_sponsor' => false,
        ]);

        return response()->json(['message' => 'Sponsor status revoked.']);
    }
}
