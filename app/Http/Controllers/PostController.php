<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Models\Thread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Request $request, Forum $forum, Thread $thread): RedirectResponse
    {
        abort_unless($thread->forum_id === $forum->id, 404);
        abort_if($thread->is_locked, 403, 'Thread is locked.');

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2'],
        ]);

        $post = $thread->posts()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $thread->update([
            'posts_count' => $thread->posts_count + 1,
            'last_post_id' => $post->id,
            'last_post_at' => $post->created_at,
        ]);

        $forum->update([
            'posts_count' => $forum->posts_count + 1,
            'last_post_id' => $post->id,
            'last_post_at' => $post->created_at,
        ]);

        return redirect()->route('threads.show', [$forum->slug, $thread->slug]);
    }
}
