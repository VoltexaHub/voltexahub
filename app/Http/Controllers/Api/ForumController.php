<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class ForumController extends Controller
{
    use \App\Http\Controllers\Concerns\ChecksForumPermissions;

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $categories = Category::with([
            'forums' => function ($q) {
                $q->whereNull('parent_forum_id')
                    ->where('is_active', true)
                    ->with(['subforums' => fn ($q) => $q->where('is_active', true), 'lastPostUser' => fn ($q) => $q->with('roles')])
                    ->orderBy('display_order');
            },
        ])
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $role = $this->getRole($request);

        $data = $categories->map(function ($cat) use ($role) {
            $visibleForums = $cat->forums->filter(function ($forum) use ($role) {
                $perms = \App\Services\PermissionService::resolve($role, $forum->id);
                return $perms['can_view'];
            });
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'header_color' => $cat->header_color,
                'total_threads' => $cat->forums->sum('thread_count'),
                'total_posts' => $cat->forums->sum('post_count'),
                'forums' => $visibleForums->map(function ($forum) use ($role) {
                    return [
                        'id' => $forum->id,
                        'name' => $forum->name,
                        'slug' => $forum->slug,
                        'description' => $forum->description,
                        'icon' => $forum->icon,
                        'thread_count' => $forum->threads()->count(),
                        'post_count' => $forum->threads()->withCount('posts')->get()->sum('posts_count'),
                        'last_post_at' => $forum->last_post_at,
                        'last_post_user' => $forum->lastPostUser ? [
                            'username' => $forum->lastPostUser->username,
                            'avatar_url' => $forum->lastPostUser->avatar_url,
                            'avatar_color' => $forum->lastPostUser->avatar_color,
                            'group_color' => $forum->lastPostUser->primary_role['color'] ?? null,
                        ] : null,
                        'last_thread' => $forum->threads()->latest('created_at')->select('id','title','slug')->first(),
                        'subforums' => $forum->subforums->filter(function ($sf) use ($role) {
                            $perms = \App\Services\PermissionService::resolve($role, $sf->id);
                            return $perms['can_view'];
                        })->map(fn ($sf) => [
                            'id' => $sf->id,
                            'name' => $sf->name,
                            'slug' => $sf->slug,
                            'description' => $sf->description,
                            'icon' => $sf->icon,
                            'thread_count' => $sf->threads()->count(),
                        ])->values(),
                    ];
                })->values(),
            ];
        });

        return response()->json(['data' => $data]);
    }
}
