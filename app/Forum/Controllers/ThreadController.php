<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Forum;
use App\Forum\Models\Post;
use App\Forum\Models\Thread;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThreadController
{
    public function show(Thread $thread): \Inertia\Response
    {
        abort_if($thread->is_deleted, 404);
        $thread->increment('views');

        $posts = Post::withoutGlobalScope('active')
            ->where('thread_id', $thread->id)
            ->where('is_deleted', false)
            ->with(['user.group', 'reactions'])
            ->oldest()
            ->paginate(20);

        return Inertia::render('Thread/Show', [
            'thread' => $thread->load(['forum.category', 'user.group']),
            'posts' => $posts,
        ]);
    }

    public function create(Forum $forum): \Inertia\Response
    {
        return Inertia::render('Thread/Create', ['forum' => $forum]);
    }

    public function store(Request $request, Forum $forum): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'body'  => ['required', 'string', 'min:10'],
        ]);

        $thread = $forum->threads()->create([
            'user_id' => $request->user()->id,
            'title'   => $data['title'],
        ]);

        $post = Post::withoutGlobalScope('active')->create([
            'thread_id' => $thread->id,
            'user_id'   => $request->user()->id,
            'body'      => $data['body'],
        ]);

        $thread->update(['last_post_id' => $post->id]);
        $forum->increment('thread_count');
        $forum->increment('post_count');
        $forum->update(['last_post_id' => $post->id]);
        $request->user()->increment('thread_count');
        $request->user()->increment('post_count');

        return redirect()->route('thread.show', $thread->slug);
    }
}
