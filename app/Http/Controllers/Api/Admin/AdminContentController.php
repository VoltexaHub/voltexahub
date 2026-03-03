<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContentController extends Controller
{
    public function threads(Request $request): JsonResponse
    {
        $query = Thread::with([
            'forum:id,name',
            'user:id,username,avatar_color,avatar_path',
        ]);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $threads = $query->latest()->paginate(25);

        $data = collect($threads->items())->map(fn (Thread $t) => [
            'id' => $t->id,
            'title' => $t->title,
            'forum_name' => $t->forum->name ?? null,
            'author_username' => $t->user->username ?? null,
            'reply_count' => $t->reply_count,
            'is_pinned' => $t->is_pinned,
            'is_locked' => $t->is_locked,
            'created_at' => $t->created_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $threads->currentPage(),
                'last_page' => $threads->lastPage(),
                'per_page' => $threads->perPage(),
                'total' => $threads->total(),
            ],
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        $query = Post::with([
            'user:id,username',
            'thread:id,title',
        ]);

        if ($search = $request->input('search')) {
            $query->where('body', 'like', "%{$search}%");
        }

        $posts = $query->latest()->paginate(25);

        $data = collect($posts->items())->map(fn (Post $p) => [
            'id' => $p->id,
            'content' => \Illuminate\Support\Str::limit($p->body, 150),
            'author_username' => $p->user->username ?? null,
            'thread_title' => $p->thread->title ?? null,
            'thread_id' => $p->thread_id,
            'created_at' => $p->created_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }
}
