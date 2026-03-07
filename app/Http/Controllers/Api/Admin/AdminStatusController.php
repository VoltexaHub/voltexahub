<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StatusCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStatusController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $history = StatusCheck::orderByDesc('checked_at')
                ->take(200)
                ->get();

            $overrides = StatusCheck::where('is_override', true)
                ->orderByDesc('checked_at')
                ->get();

            return response()->json([
                'history' => $history,
                'overrides' => $overrides,
            ]);
        } catch (\Illuminate\Database\QueryException) {
            return response()->json(['history' => [], 'overrides' => []]);
        }
    }

    public function override(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service' => 'required|string|in:forum,database,websocket,queue',
            'status' => 'required|in:operational,degraded,outage',
            'message' => 'nullable|string|max:500',
        ]);

        $check = StatusCheck::create([
            'service' => $validated['service'],
            'status' => $validated['status'],
            'message' => $validated['message'] ?? null,
            'is_override' => true,
            'checked_at' => now(),
        ]);

        return response()->json(['data' => $check], 201);
    }

    public function clearOverride(string $service): JsonResponse
    {
        StatusCheck::where('service', $service)
            ->where('is_override', true)
            ->delete();

        return response()->json(['message' => "Override cleared for {$service}."]);
    }

    public function clearAllOverrides(): JsonResponse
    {
        StatusCheck::where('is_override', true)->delete();

        return response()->json(['message' => 'All overrides cleared.']);
    }
}
