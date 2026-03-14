<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    public function recent(): JsonResponse
    {
        $posts = Post::with([
            'thread:id,title,slug',
            'user' => fn ($q) => $q->select('id', 'username', 'avatar_color', 'avatar_path')->with('roles'),
        ])->latest()->take(8)->get();

        return response()->json([
            'data' => $posts->map(function ($post) {
                $user = $post->user;
                $groupColor = $user
                    ? ($user->roles->first(fn ($r) => $r->name !== 'banned') ?? $user->roles->first())?->color ?? null
                    : null;

                return [
                    'post_id' => $post->id,
                    'thread_id' => $post->thread_id,
                    'thread_title' => $post->thread?->title,
                    'thread_slug' => $post->thread?->slug,
                    'user' => $user ? [
                        'username' => $user->username,
                        'avatar_url' => $user->avatar_path ? asset('storage/' . $user->avatar_path) : null,
                        'avatar_color' => $user->avatar_color,
                        'group_color' => $groupColor,
                    ] : null,
                    'created_at' => $post->created_at,
                ];
            }),
        ]);
    }
}
