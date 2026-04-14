<?php

namespace App\Http\Controllers;

use App\Events\PostReactionUpdated;
use App\Models\Post;
use App\Models\Reaction;
use App\Notifications\PostReactionReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReactionController extends Controller
{
    public function toggle(Request $request, Post $post): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'emoji' => ['required', 'string', Rule::in(Reaction::ALLOWED)],
        ]);

        $userId = $request->user()->id;

        $existing = $post->reactions()
            ->where('user_id', $userId)
            ->where('emoji', $data['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            Reaction::create([
                'post_id' => $post->id,
                'user_id' => $userId,
                'emoji' => $data['emoji'],
            ]);

            if ($post->user_id && $post->user_id !== $userId) {
                $post->author?->notify(new PostReactionReceived(
                    $post,
                    $request->user(),
                    $data['emoji'],
                ));
            }
        }

        $post->load('reactions');
        broadcast(new PostReactionUpdated($post))->toOthers();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'post_id' => $post->id,
                'summary' => $post->reactionSummary($userId),
            ]);
        }

        return back();
    }
}
