<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminErrorLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ErrorLog::query()->latest('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $logs = $query->paginate(20);

        return response()->json($logs);
    }

    public function destroy(int $id): JsonResponse
    {
        $log = ErrorLog::findOrFail($id);
        $log->delete();

        return response()->json(null, 204);
    }

    public function clear(): JsonResponse
    {
        ErrorLog::truncate();

        return response()->json(['message' => 'Error log cleared.']);
    }

    public function getSettings(): JsonResponse
    {
        return response()->json([
            'enabled' => ForumConfig::get('error_log_enabled', 'false') === 'true',
            'prune_days' => (int) ForumConfig::get('error_log_prune_days', '30'),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
            'prune_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        ForumConfig::set('error_log_enabled', $request->enabled ? 'true' : 'false');
        ForumConfig::set('error_log_prune_days', (string) $request->prune_days);

        return response()->json([
            'enabled' => $request->boolean('enabled'),
            'prune_days' => (int) $request->prune_days,
        ]);
    }
}
