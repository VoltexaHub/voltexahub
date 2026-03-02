<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAchievementController extends Controller
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'trigger_type' => ['required', 'string', 'max:50'],
            'trigger_key' => ['required', 'string', 'max:50'],
            'trigger_value' => ['required', 'integer', 'min:1'],
            'credits_reward' => ['nullable', 'integer', 'min:0'],
        ]);

        $achievement = Achievement::create($validated);

        return response()->json([
            'data' => $achievement,
            'message' => 'Achievement created successfully.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $achievement = Achievement::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'trigger_type' => ['sometimes', 'string', 'max:50'],
            'trigger_key' => ['sometimes', 'string', 'max:50'],
            'trigger_value' => ['sometimes', 'integer', 'min:1'],
            'credits_reward' => ['nullable', 'integer', 'min:0'],
        ]);

        $achievement->update($validated);

        return response()->json([
            'data' => $achievement->fresh(),
            'message' => 'Achievement updated successfully.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $achievement = Achievement::findOrFail($id);
        $achievement->delete();

        return response()->json([
            'message' => 'Achievement deleted successfully.',
        ]);
    }
}
