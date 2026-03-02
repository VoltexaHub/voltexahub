<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with(['game', 'forums' => function ($q) {
            $q->where('is_active', true)
                ->orderBy('display_order')
                ->with('lastPostUser', 'subforums');
        }])
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }
}
