<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Models\Thread;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ThreadController extends Controller
{
    public function show(Forum $forum, Thread $thread): View
    {
        abort_unless($thread->forum_id === $forum->id, 404);

        $thread->increment('views_count');

        $posts = $thread->posts()
            ->with('author:id,name')
            ->orderBy('created_at')
            ->paginate(20);

        $thread->load('author:id,name');
        $forum->load('category:id,name,slug');

        return view('theme::thread-show', compact('forum', 'thread', 'posts'));
    }

    public function create(Forum $forum): View
    {
        return view('theme::thread-create', compact('forum'));
    }

    public function store(Request $request, Forum $forum): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'body' => ['required', 'string', 'min:2'],
        ]);

        $thread = $forum->threads()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::random(6),
        ]);

        $post = $thread->posts()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $thread->update([
            'posts_count' => 1,
            'last_post_id' => $post->id,
            'last_post_at' => $post->created_at,
        ]);

        $forum->update([
            'threads_count' => $forum->threads()->count(),
            'posts_count' => $forum->posts_count + 1,
            'last_post_id' => $post->id,
            'last_post_at' => $post->created_at,
        ]);

        return redirect()->route('threads.show', [$forum->slug, $thread->slug]);
    }
}
