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
    private const XP_TIERS = [
        500000 => 20, 300000 => 19, 200000 => 18, 150000 => 17, 100000 => 16,
        75000 => 15, 50000 => 14, 30000 => 13, 20000 => 12, 15000 => 11,
        10000 => 10, 7500 => 9, 5000 => 8, 3500 => 7, 2000 => 6,
        1000 => 5, 500 => 4, 250 => 3, 100 => 2, 0 => 1,
    ];

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
            'xp' => $this->byXp($limit),
            default => $this->byCredits($limit),
        };

        $authUser = $request->user();
        $yourRank = $authUser ? $this->computeYourRank($authUser, $type, $periodStart, $entries) : null;

        return response()->json([
            'data' => $entries,
            'your_rank' => $yourRank,
        ]);
    }

    private function byCredits(int $limit): array
    {
        $users = User::select('id', 'username', 'avatar_color', 'avatar_path', 'credits', 'user_title', 'xp', 'created_at')
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

    private function byXp(int $limit): array
    {
        $users = User::select('id', 'username', 'avatar_color', 'avatar_path', 'credits', 'user_title', 'xp', 'created_at')
            ->with('roles')
            ->orderByDesc('xp')
            ->limit($limit)
            ->get();

        return $users->values()->map(fn ($u, $i) => [
            'rank' => $i + 1,
            'user' => $this->formatUser($u),
            'value' => $u->xp,
            'label' => 'xp',
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

        $users = User::select('id', 'username', 'avatar_color', 'avatar_path', 'credits', 'user_title', 'xp', 'created_at')
            ->with('roles')
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

        $users = User::select('id', 'username', 'avatar_color', 'avatar_path', 'credits', 'user_title', 'xp', 'created_at')
            ->with('roles')
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

    private function computeYourRank(User $authUser, string $type, ?Carbon $periodStart, array $entries): array
    {
        $inTop = false;
        $rank = null;
        $value = null;

        foreach ($entries as $entry) {
            if ($entry['user'] && $entry['user']['id'] === $authUser->id) {
                $inTop = true;
                $rank = $entry['rank'];
                $value = $entry['value'];
                break;
            }
        }

        if (! $inTop) {
            [$rank, $value] = match ($type) {
                'credits' => [
                    User::where('credits', '>', $authUser->credits)->count() + 1,
                    $authUser->credits,
                ],
                'xp' => [
                    User::where('xp', '>', $authUser->xp)->count() + 1,
                    $authUser->xp,
                ],
                'posts' => $this->countRank(Post::query(), 'user_id', $authUser->id, $periodStart),
                'threads' => $this->countRank(Thread::query(), 'user_id', $authUser->id, $periodStart),
                'reactions' => $this->reactionsRank($authUser->id, $periodStart),
                default => [
                    User::where('credits', '>', $authUser->credits)->count() + 1,
                    $authUser->credits,
                ],
            };
        }

        return [
            'rank' => $rank,
            'value' => $value,
            'in_top' => $inTop,
        ];
    }

    private function countRank($query, string $column, int $userId, ?Carbon $periodStart): array
    {
        $baseQuery = clone $query;

        if ($periodStart) {
            $query->where('created_at', '>=', $periodStart);
            $baseQuery->where('created_at', '>=', $periodStart);
        }

        $userCount = $baseQuery->where($column, $userId)->count();

        $rank = $query->selectRaw("{$column}, count(*) as cnt")
            ->groupBy($column)
            ->havingRaw('count(*) > ?', [$userCount])
            ->count() + 1;

        return [$rank, $userCount];
    }

    private function reactionsRank(int $userId, ?Carbon $periodStart): array
    {
        $baseQuery = Reaction::query()->join('posts', 'reactions.post_id', '=', 'posts.id')
            ->where('posts.user_id', $userId);

        if ($periodStart) {
            $baseQuery->where('reactions.created_at', '>=', $periodStart);
        }

        $userCount = $baseQuery->count();

        $rankQuery = Reaction::query()->join('posts', 'reactions.post_id', '=', 'posts.id');

        if ($periodStart) {
            $rankQuery->where('reactions.created_at', '>=', $periodStart);
        }

        $rank = $rankQuery->selectRaw('posts.user_id, count(*) as cnt')
            ->groupBy('posts.user_id')
            ->havingRaw('count(*) > ?', [$userCount])
            ->count() + 1;

        return [$rank, $userCount];
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
            'user_title' => $user->user_title,
            'xp' => $user->xp,
            'level' => self::xpToLevel($user->xp ?? 0),
            'join_date' => $user->created_at->format('Y-m-d'),
        ];
    }

    private static function xpToLevel(int $xp): int
    {
        foreach (self::XP_TIERS as $threshold => $level) {
            if ($xp >= $threshold) {
                return $level;
            }
        }

        return 1;
    }
}
