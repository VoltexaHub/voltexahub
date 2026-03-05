<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolvedController extends Controller
{
    public function markSolved(Request $request, Thread $thread): JsonResponse
    {
        $user = $request->user();

        if ($thread->user_id !== $user->id && ! $user->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'post_id' => ['required', 'integer'],
        ]);

        $post = $thread->posts()->where('id', $validated['post_id'])->firstOrFail();

        if ($post->is_first_post) {
            return response()->json(['message' => 'Cannot mark the first post as the solution.'], 422);
        }

        $thread->update([
            'is_solved' => true,
            'solved_post_id' => $post->id,
        ]);

        // Award credits to the post author (not the thread author)
        if ($post->user_id !== $thread->user_id) {
            $credits = (int) ForumConfig::get('credits_for_solved', 25);
            if ($credits > 0) {
                $post->user->addCredits($credits, 'Best answer accepted', Thread::class, $thread->id);
            }
        }

        return response()->json([
            'data' => $thread->fresh()->load('solvedPost.user:id,username'),
        ]);
    }

    public function unmarkSolved(Request $request, Thread $thread): JsonResponse
    {
        $user = $request->user();

        if ($thread->user_id !== $user->id && ! $user->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $thread->update([
            'is_solved' => false,
            'solved_post_id' => null,
        ]);

        return response()->json([
            'data' => $thread->fresh(),
        ]);
    }
}
