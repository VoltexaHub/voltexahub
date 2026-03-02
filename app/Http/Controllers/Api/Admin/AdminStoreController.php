<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverPurchase;
use App\Models\StoreItem;
use App\Models\StorePurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminStoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = StoreItem::with('game')
            ->orderBy('display_order')
            ->paginate(20);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:store_items,slug'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'price_money' => ['nullable', 'numeric', 'min:0'],
            'price_credits' => ['nullable', 'integer', 'min:0'],
            'supports_both' => ['nullable', 'boolean'],
            'item_type' => ['required', 'string', 'max:50'],
            'item_value' => ['nullable', 'string', 'max:255'],
            'game_id' => ['nullable', 'exists:games,id'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $item = StoreItem::create($validated);

        return response()->json([
            'data' => $item->load('game'),
            'message' => 'Store item created successfully.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = StoreItem::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:store_items,slug,' . $id],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'price_money' => ['nullable', 'numeric', 'min:0'],
            'price_credits' => ['nullable', 'integer', 'min:0'],
            'supports_both' => ['nullable', 'boolean'],
            'item_type' => ['sometimes', 'string', 'max:50'],
            'item_value' => ['nullable', 'string', 'max:255'],
            'game_id' => ['nullable', 'exists:games,id'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer'],
        ]);

        $item->update($validated);

        return response()->json([
            'data' => $item->fresh()->load('game'),
            'message' => 'Store item updated successfully.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = StoreItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Store item deleted successfully.',
        ]);
    }

    public function purchases(Request $request): JsonResponse
    {
        $query = StorePurchase::with(['user:id,username', 'storeItem:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $purchases = $query->latest()->paginate(20);

        return response()->json([
            'data' => $purchases->items(),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    public function deliver(int $id): JsonResponse
    {
        $purchase = StorePurchase::findOrFail($id);

        $purchase->update([
            'status' => 'completed',
            'delivered_at' => now(),
        ]);

        dispatch(new DeliverPurchase($purchase));

        return response()->json([
            'data' => $purchase->fresh()->load(['user:id,username', 'storeItem:id,name']),
            'message' => 'Purchase marked as delivered. Delivery job dispatched.',
        ]);
    }
}
