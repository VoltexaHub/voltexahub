<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Report::with([
            'reporter:id,username,avatar_color,avatar_path',
            'post' => fn ($q) => $q->with('user:id,username,avatar_color,avatar_path'),
            'thread',
        ]);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $reports = $query->latest()->paginate(20);

        return response()->json([
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = Report::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:reviewed,dismissed'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'data' => $report->fresh()->load('reporter:id,username'),
            'message' => 'Report updated successfully.',
        ]);
    }
}
