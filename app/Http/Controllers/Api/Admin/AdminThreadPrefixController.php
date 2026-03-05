<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThreadPrefix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminThreadPrefixController extends Controller
{
    public function index(): JsonResponse
    {
        $prefixes = ThreadPrefix::orderBy('display_order')->get();

        return response()->json(['data' => $prefixes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'bg_color' => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'display_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $prefix = ThreadPrefix::create($validated);

        return response()->json(['data' => $prefix], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $prefix = ThreadPrefix::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'bg_color' => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'display_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $prefix->update($validated);

        return response()->json(['data' => $prefix]);
    }

    public function destroy(int $id): JsonResponse
    {
        $prefix = ThreadPrefix::findOrFail($id);
        $prefix->delete();

        return response()->json(['message' => 'Prefix deleted.']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:thread_prefixes,id'],
            'items.*.order' => ['required', 'integer'],
        ]);

        foreach ($validated['items'] as $item) {
            ThreadPrefix::where('id', $item['id'])->update(['display_order' => $item['order']]);
        }

        return response()->json(['message' => 'Order updated.']);
    }
}
