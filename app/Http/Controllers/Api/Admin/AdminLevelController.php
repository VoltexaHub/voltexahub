<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use App\Models\Level;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLevelController extends Controller
{
    public function index(): JsonResponse
    {
        $levels = Level::orderBy('level')->get();

        return response()->json(['data' => $levels]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:1', 'unique:levels,level'],
            'xp_required' => ['required', 'integer', 'min:0'],
            'label' => ['nullable', 'string', 'max:50'],
        ]);

        $level = Level::create($validated);

        return response()->json(['data' => $level], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $level = Level::findOrFail($id);

        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:1', 'unique:levels,level,' . $id],
            'xp_required' => ['required', 'integer', 'min:0'],
            'label' => ['nullable', 'string', 'max:50'],
        ]);

        $level->update($validated);

        return response()->json(['data' => $level]);
    }

    public function destroy(int $id): JsonResponse
    {
        Level::findOrFail($id)->delete();

        return response()->json(['message' => 'Level deleted.']);
    }

    public function preset(): JsonResponse
    {
        Level::truncate();

        $presets = [
            ['level' => 1, 'xp_required' => 0, 'label' => 'Newcomer'],
            ['level' => 2, 'xp_required' => 100, 'label' => 'Member'],
            ['level' => 3, 'xp_required' => 250, 'label' => 'Regular'],
            ['level' => 4, 'xp_required' => 500, 'label' => 'Active'],
            ['level' => 5, 'xp_required' => 1000, 'label' => 'Contributor'],
            ['level' => 6, 'xp_required' => 1750, 'label' => 'Veteran'],
            ['level' => 7, 'xp_required' => 2750, 'label' => 'Senior'],
            ['level' => 8, 'xp_required' => 4000, 'label' => 'Expert'],
            ['level' => 9, 'xp_required' => 5500, 'label' => 'Elite'],
            ['level' => 10, 'xp_required' => 7500, 'label' => 'Legend'],
            ['level' => 11, 'xp_required' => 10000, 'label' => 'Mythic'],
            ['level' => 12, 'xp_required' => 13000, 'label' => 'Heroic'],
            ['level' => 13, 'xp_required' => 16500, 'label' => 'Champion'],
            ['level' => 14, 'xp_required' => 20500, 'label' => 'Master'],
            ['level' => 15, 'xp_required' => 25000, 'label' => 'Grandmaster'],
            ['level' => 16, 'xp_required' => 30000, 'label' => 'Titan'],
            ['level' => 17, 'xp_required' => 36000, 'label' => 'Immortal'],
            ['level' => 18, 'xp_required' => 43000, 'label' => 'Ascendant'],
            ['level' => 19, 'xp_required' => 51000, 'label' => 'Divine'],
            ['level' => 20, 'xp_required' => 60000, 'label' => 'Transcendent'],
        ];

        foreach ($presets as $preset) {
            Level::create($preset);
        }

        return response()->json([
            'data' => Level::orderBy('level')->get(),
            'message' => 'Preset levels loaded.',
        ]);
    }

    public function xpSettings(): JsonResponse
    {
        return response()->json([
            'data' => [
                'xp_post_created' => (int) ForumConfig::get('xp_post_created', 10),
                'xp_thread_created' => (int) ForumConfig::get('xp_thread_created', 20),
                'xp_like_received' => (int) ForumConfig::get('xp_like_received', 5),
            ],
        ]);
    }

    public function updateXpSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'xp_post_created' => ['required', 'integer', 'min:0'],
            'xp_thread_created' => ['required', 'integer', 'min:0'],
            'xp_like_received' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated as $key => $value) {
            ForumConfig::set($key, $value);
        }

        return response()->json(['message' => 'XP settings updated.']);
    }
}
