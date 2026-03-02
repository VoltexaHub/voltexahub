<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;

class CreditsController extends Controller
{
    public function earningInfo(): JsonResponse
    {
        return response()->json([
            'data' => [
                'ways_to_earn' => [
                    ['action' => 'Create a thread', 'amount' => (int) ForumConfig::get('credits_per_thread', 10), 'icon' => 'fa-solid fa-pen-to-square'],
                    ['action' => 'Post a reply', 'amount' => (int) ForumConfig::get('credits_per_reply', 5), 'icon' => 'fa-solid fa-comment'],
                    ['action' => 'Reply marked as solution', 'amount' => (int) ForumConfig::get('credits_for_solved', 25), 'icon' => 'fa-solid fa-circle-check'],
                    ['action' => 'Receive a like', 'amount' => (int) ForumConfig::get('credits_per_like', 1), 'icon' => 'fa-solid fa-heart'],
                ],
                'multipliers' => json_decode(ForumConfig::get('role_credit_multipliers', '{"member":1.0}'), true),
            ],
        ]);
    }
}
