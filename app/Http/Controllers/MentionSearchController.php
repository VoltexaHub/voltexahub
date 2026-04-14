<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MentionSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return response()->json(['results' => []]);
        }

        $like = '%'.addcslashes($q, '%_\\').'%';
        $caseInsensitive = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $users = User::query()
            ->where(function ($w) use ($q, $like, $caseInsensitive) {
                $w->where('handle', $caseInsensitive, $q.'%')
                  ->orWhere('name', $caseInsensitive, $like);
            })
            ->orderByRaw("CASE WHEN handle {$caseInsensitive} ? THEN 0 ELSE 1 END", [$q.'%'])
            ->limit(8)
            ->get(['id', 'handle', 'name', 'avatar_path', 'oauth_avatar', 'email']);

        return response()->json([
            'results' => $users->map(fn ($u) => [
                'id' => $u->id,
                'handle' => $u->handle,
                'name' => $u->name,
                'avatar_url' => $u->avatar_url,
            ]),
        ]);
    }
}
