<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminAwardController extends Controller
{
    public function index(): JsonResponse
    {
        $awards = Award::orderBy('display_order')
            ->with('achievement:id,name')
            ->get();

        return response()->json([
            'data' => $awards,
        ]);
    }

    public function store(Request $request, ImageUploadService $imageService): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'icon_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'type' => ['sometimes', 'in:manual,achievement,purchasable'],
            'achievement_id' => ['nullable', 'exists:achievements,id'],
            'price_credits' => ['nullable', 'integer', 'min:0'],
            'price_money' => ['nullable', 'numeric', 'min:0'],
            'display_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        unset($validated['icon_file']);

        if ($request->hasFile('icon_file')) {
            $validated['icon_path'] = $imageService->store($request->file('icon_file'), 'awards', 256, 256, 90, true);
            $validated['icon'] = null; // clear text icon when file uploaded
        } else {
            $validated['icon_path'] = null; // ensure no stale path
        }

        $award = Award::create($validated);

        return response()->json([
            'data' => $award,
            'message' => 'Award created successfully.',
        ], 201);
    }

    public function update(Request $request, int $id, ImageUploadService $imageService): JsonResponse
    {
        $award = Award::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'icon_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'type' => ['sometimes', 'in:manual,achievement,purchasable'],
            'achievement_id' => ['nullable', 'exists:achievements,id'],
            'price_credits' => ['nullable', 'integer', 'min:0'],
            'price_money' => ['nullable', 'numeric', 'min:0'],
            'display_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        unset($validated['icon_file']);

        if ($request->hasFile('icon_file')) {
            if ($award->icon_path) {
                Storage::disk('public')->delete($award->icon_path);
            }
            $validated['icon_path'] = $imageService->store($request->file('icon_file'), 'awards', 256, 256, 90, true);
            $validated['icon'] = null; // clear text icon when file uploaded
        } elseif (isset($validated['icon']) && $validated['icon']) {
            // switching back to text icon — clear any old uploaded file
            if ($award->icon_path) {
                Storage::disk('public')->delete($award->icon_path);
            }
            $validated['icon_path'] = null;
        }

        $award->update($validated);

        return response()->json([
            'data' => $award->fresh(),
            'message' => 'Award updated successfully.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'awards' => ['required', 'array'],
            'awards.*.id' => ['required', 'exists:awards,id'],
            'awards.*.display_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['awards'] as $item) {
            Award::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        return response()->json(['message' => 'Awards reordered successfully.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $award = Award::findOrFail($id);

        if ($award->icon_path) {
            Storage::disk('public')->delete($award->icon_path);
        }

        $award->delete();

        return response()->json([
            'message' => 'Award deleted successfully.',
        ]);
    }
}
