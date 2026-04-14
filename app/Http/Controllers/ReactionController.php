<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Reaction;
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
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            $post->load('reactions');

            return response()->json([
                'post_id' => $post->id,
                'summary' => $post->reactionSummary($userId),
            ]);
        }

        return back();
    }
}
