<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Award;
use Illuminate\Http\JsonResponse;

class AwardController extends Controller
{
    public function index(): JsonResponse
    {
        $awards = Award::orderBy('display_order')
            ->get()
            ->each(function ($award) {
                if ($award->achievement_id) {
                    $award->achievement_name = $award->achievement?->name;
                }
            });

        return response()->json([
            'data' => $awards,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $award = Award::findOrFail($id);

        if ($award->achievement_id) {
            $award->achievement_name = $award->achievement?->name;
        }

        $holders = $award->userAwards()
            ->join('users', 'users.id', '=', 'user_awards.user_id')
            ->select('user_awards.user_id', 'users.username', 'users.avatar_url', 'user_awards.created_at as granted_at')
            ->orderByDesc('user_awards.created_at')
            ->paginate(15);

        return response()->json([
            'data' => $award,
            'holders' => $holders,
        ]);
    }
}
