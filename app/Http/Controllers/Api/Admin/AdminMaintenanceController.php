<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AdminMaintenanceController extends Controller
{
    public function run(Request $request, string $tool): JsonResponse
    {
        switch ($tool) {
            case 'rebuild-forum-stats':
                $forums = Forum::all();
                foreach ($forums as $forum) {
                    $forum->thread_count = $forum->threads()->count();
                    $forum->post_count = DB::table('posts')
                        ->whereIn('thread_id', $forum->threads()->pluck('id'))
                        ->count();
                    $forum->save();
                }

                return response()->json(['message' => $forums->count() . ' forums updated.']);

            case 'rebuild-user-post-counts':
                $users = User::all();
                foreach ($users as $user) {
                    $user->post_count = $user->posts()->count();
                    $user->save();
                }

                return response()->json(['message' => $users->count() . ' users updated.']);

            case 'prune-sessions':
                $deleted = DB::table('personal_access_tokens')
                    ->where(function ($q) {
                        $q->whereNotNull('expires_at')->where('expires_at', '<', now());
                    })
                    ->orWhere(function ($q) {
                        $q->whereNotNull('last_used_at')->where('last_used_at', '<', now()->subDays(30));
                    })
                    ->delete();

                return response()->json(['message' => $deleted . ' expired sessions pruned.']);

            case 'prune-audit-log':
                $days = max(7, (int) $request->input('days', 90));
                $deleted = DB::table('audit_logs')
                    ->where('created_at', '<', now()->subDays($days))
                    ->delete();

                return response()->json(['message' => $deleted . ' audit log entries deleted.']);

            case 'clear-cache':
                Artisan::call('cache:clear');
                Artisan::call('config:clear');

                return response()->json(['message' => 'Cache cleared successfully.']);

            default:
                return response()->json(['message' => 'Unknown tool.'], 400);
        }
    }
}
