<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LockedContentUnlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LockedContentReportController extends Controller
{
    /** GET /api/locked-content/{hash}/status */
    public function status(Request $request, string $hash): JsonResponse
    {
        $counts = DB::table('locked_content_reports')
            ->where('content_hash', $hash)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $userVote = null;
        if ($request->user()) {
            $userVote = DB::table('locked_content_reports')
                ->where('content_hash', $hash)
                ->where('user_id', $request->user()->id)
                ->value('status');
        }

        return response()->json([
            'working'     => (int) ($counts['working'] ?? 0),
            'not_working' => (int) ($counts['not_working'] ?? 0),
            'user_vote'   => $userVote,
        ]);
    }

    /** POST /api/locked-content/{hash}/report */
    public function report(Request $request, string $hash): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:working,not_working'],
        ]);

        $user = $request->user();

        // Must have unlocked the content (or be admin/mod)
        $hasAccess = $user->hasRole(['admin', 'moderator'])
            || LockedContentUnlock::where('user_id', $user->id)
                ->where('content_hash', $hash)
                ->exists();

        if (! $hasAccess) {
            return response()->json(['message' => 'You must unlock this content before rating it.'], 403);
        }

        DB::table('locked_content_reports')->upsert(
            [
                'content_hash' => $hash,
                'user_id'      => $user->id,
                'status'       => $validated['status'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            ['content_hash', 'user_id'],
            ['status', 'updated_at']
        );

        // Return fresh counts
        $counts = DB::table('locked_content_reports')
            ->where('content_hash', $hash)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'working'     => (int) ($counts['working'] ?? 0),
            'not_working' => (int) ($counts['not_working'] ?? 0),
            'user_vote'   => $validated['status'],
        ]);
    }
}
