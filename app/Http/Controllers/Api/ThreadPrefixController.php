<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThreadPrefix;
use Illuminate\Http\JsonResponse;

class ThreadPrefixController extends Controller
{
    public function index(): JsonResponse
    {
        $prefixes = ThreadPrefix::where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'name', 'color', 'bg_color', 'text_color']);

        return response()->json(['data' => $prefixes]);
    }
}
