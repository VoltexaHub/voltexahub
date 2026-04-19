<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Post;
use App\Forum\Models\Thread;
use Illuminate\Http\Request;

class PostController
{
    public function store(Request $request, Thread $thread): \Illuminate\Http\RedirectResponse
    {
        abort_if($thread->is_locked, 403, 'Thread is locked.');

        $data = $request->validate(['body' => ['required', 'string', 'min:1']]);

        $post = Post::withoutGlobalScope('active')->create([
            'thread_id' => $thread->id,
            'user_id'   => $request->user()->id,
            'body'      => $data['body'],
        ]);

        $thread->increment('reply_count');
        $thread->update(['last_post_id' => $post->id]);
        $thread->forum->increment('post_count');
        $thread->forum->update(['last_post_id' => $post->id]);
        $request->user()->increment('post_count');

        return redirect()->route('thread.show', $thread->slug)
            ->with('success', 'Reply posted.');
    }

    public function update(Request $request, Post $post): \Illuminate\Http\RedirectResponse
    {
        $post = Post::withoutGlobalScope('active')->findOrFail($post->id);
        abort_unless($request->user()->id === $post->user_id || $request->user()->isModerator(), 403);

        $data = $request->validate(['body' => ['required', 'string', 'min:1']]);
        $post->update([
            'body'         => $data['body'],
            'edited_at'    => now(),
            'edited_by_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Post updated.');
    }

    public function destroy(Request $request, Post $post): \Illuminate\Http\RedirectResponse
    {
        $post = Post::withoutGlobalScope('active')->findOrFail($post->id);
        abort_unless($request->user()->id === $post->user_id || $request->user()->isModerator(), 403);

        $post->update(['is_deleted' => true]);
        return back()->with('success', 'Post deleted.');
    }
}
