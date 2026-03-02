<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Http\JsonResponse;

class AchievementController extends Controller
{
    public function index(): JsonResponse
    {
        $achievements = Achievement::orderBy('category')
            ->orderBy('trigger_value')
            ->get();

        return response()->json([
            'data' => $achievements,
        ]);
    }
}
