<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostEditController extends Controller
{
    public function edit(Request $request, Post $post): View
    {
        $this->authorizeEdit($request, $post);

        $post->load(['thread.forum:id,slug,name', 'author:id,name']);

        return view('theme::post-edit', compact('post'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeEdit($request, $post);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2'],
        ]);

        $post->update([
            'body' => $data['body'],
            'edited_at' => now(),
            'edited_by' => $request->user()->id,
        ]);

        $thread = $post->thread;

        return redirect()->route('threads.show', [$thread->forum->slug, $thread->slug])
            ->with('flash.success', 'Post updated.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->is_admin || $user->id === $post->user_id), 403);

        $thread = $post->thread;
        $forum = $thread->forum;

        if ($thread->posts()->count() <= 1) {
            return back()->with('flash.error', 'Cannot delete the only post in a thread. Delete the thread instead.');
        }

        $post->delete();

        $newLast = $thread->posts()->latest('created_at')->first();
        $thread->update([
            'posts_count' => $thread->posts()->count(),
            'last_post_id' => $newLast?->id,
            'last_post_at' => $newLast?->created_at,
        ]);

        $forumLast = \App\Models\Post::whereIn('thread_id', $forum->threads()->pluck('id'))->latest('created_at')->first();
        $forum->update([
            'posts_count' => \App\Models\Post::whereIn('thread_id', $forum->threads()->pluck('id'))->count(),
            'last_post_id' => $forumLast?->id,
            'last_post_at' => $forumLast?->created_at,
        ]);

        return back()->with('flash.success', 'Post deleted.');
    }

    private function authorizeEdit(Request $request, Post $post): void
    {
        $user = $request->user();
        abort_unless($user && ($user->is_admin || $user->id === $post->user_id), 403);
    }
}
