<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'thread_id' => ['nullable', 'integer', 'exists:threads,id'],
        ]);

        if (empty($validated['post_id']) && empty($validated['thread_id'])) {
            return response()->json([
                'message' => 'Either post_id or thread_id is required.',
            ], 422);
        }

        $user = $request->user();

        // Prevent duplicate reports
        $query = Report::where('reporter_id', $user->id);
        if (! empty($validated['post_id'])) {
            $query->where('post_id', $validated['post_id']);
        }
        if (! empty($validated['thread_id'])) {
            $query->where('thread_id', $validated['thread_id']);
        }
        if ($query->exists()) {
            return response()->json([
                'message' => 'You have already reported this content.',
            ], 409);
        }

        $report = Report::create([
            'reporter_id' => $user->id,
            'post_id' => $validated['post_id'] ?? null,
            'thread_id' => $validated['thread_id'] ?? null,
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'data' => $report,
            'message' => 'Report submitted successfully.',
        ], 201);
    }
}
