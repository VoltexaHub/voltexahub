<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with(['forums' => function ($q) {
            $q->whereNull('parent_forum_id')
                ->where('is_active', true)
                ->orderBy('display_order')
                ->with(['lastPostUser', 'subforums' => fn ($q) => $q->where('is_active', true)->orderBy('display_order')]);
        }])
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        // Compute category-level last post from the forum with the most recent activity
        foreach ($categories as $category) {
            $latestForum = null;

            foreach ($category->forums as $forum) {
                if ($forum->last_post_at && (!$latestForum || $forum->last_post_at > $latestForum->last_post_at)) {
                    $latestForum = $forum;
                }
            }

            if ($latestForum) {
                $category->last_post_at = $latestForum->last_post_at;
                $category->last_post_user = $latestForum->lastPostUser;
                $category->last_post_thread = Thread::where('forum_id', $latestForum->id)
                    ->latest('created_at')
                    ->select('id', 'title', 'slug')
                    ->first();
            } else {
                $category->last_post_at = null;
                $category->last_post_user = null;
                $category->last_post_thread = null;
            }
        }

        foreach ($categories as $category) {
            $category->total_threads = $category->forums->sum('thread_count');
            $category->total_posts = $category->forums->sum('post_count');
        }

        return response()->json([
            'data' => $categories,
        ]);
    }
}
