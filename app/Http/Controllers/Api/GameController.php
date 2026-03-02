<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;

class GameController extends Controller
{
    public function index(): JsonResponse
    {
        $games = Game::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'data' => $games,
        ]);
    }
}
