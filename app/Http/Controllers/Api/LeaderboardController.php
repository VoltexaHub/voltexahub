<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaderboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $request->input('type', 'credits');
        $period = $request->input('period', 'all');
        $limit = 50;

        $periodStart = match ($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        $entries = match ($type) {
            'posts' => $this->byCount(Post::query(), 'user_id', $periodStart, $limit),
            'threads' => $this->byCount(Thread::query(), 'user_id', $periodStart, $limit),
            'reactions' => $this->reactionsReceived($periodStart, $limit),
            default => $this->byCredits($limit),
        };

        return response()->json(['data' => $entries]);
    }

    private function byCredits(int $limit): array
    {
        $users = User::select('id', 'username', 'avatar_color', 'avatar_path', 'credits')
            ->with('roles')
            ->orderByDesc('credits')
            ->limit($limit)
            ->get();

        return $users->values()->map(fn ($u, $i) => [
            'rank' => $i + 1,
            'user' => $this->formatUser($u),
            'value' => $u->credits,
            'label' => 'credits',
        ])->all();
    }

    private function byCount($query, string $column, ?Carbon $periodStart, int $limit): array
    {
        if ($periodStart) {
            $query->where('created_at', '>=', $periodStart);
        }

        $rows = $query->selectRaw("{$column}, count(*) as cnt")
            ->groupBy($column)
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $users = User::with('roles')
            ->whereIn('id', $rows->pluck($column))
            ->get()
            ->keyBy('id');

        return $rows->values()->map(fn ($row, $i) => [
            'rank' => $i + 1,
            'user' => $this->formatUser($users[$row->{$column}] ?? null),
            'value' => $row->cnt,
            'label' => str_contains($query->getModel()->getTable(), 'post') ? 'posts' : 'threads',
        ])->all();
    }

    private function reactionsReceived(?Carbon $periodStart, int $limit): array
    {
        $query = Reaction::query()
            ->join('posts', 'reactions.post_id', '=', 'posts.id');

        if ($periodStart) {
            $query->where('reactions.created_at', '>=', $periodStart);
        }

        $rows = $query->selectRaw('posts.user_id, count(*) as cnt')
            ->groupBy('posts.user_id')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $users = User::with('roles')
            ->whereIn('id', $rows->pluck('user_id'))
            ->get()
            ->keyBy('id');

        return $rows->values()->map(fn ($row, $i) => [
            'rank' => $i + 1,
            'user' => $this->formatUser($users[$row->user_id] ?? null),
            'value' => $row->cnt,
            'label' => 'reactions',
        ])->all();
    }

    private function formatUser(?User $user): ?array
    {
        if (! $user) return null;

        $role = $user->roles->first();

        return [
            'id' => $user->id,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url ?? null,
            'group_color' => $role->color ?? null,
            'group_label' => $role->label ?? null,
            'group_name' => $role->name ?? null,
        ];
    }
}
