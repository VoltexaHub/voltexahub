<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class ForumController extends Controller
{
    public function index(): JsonResponse
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

        $data = $categories->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'forums' => $cat->forums->map(function ($forum) {
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
                        ] : null,
                        'subforums' => $forum->subforums->map(fn ($sf) => [
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
