<?php

namespace App\Http\Controllers;

use App\Events\PostCreated;
use App\Models\Forum;
use App\Models\Thread;
use App\Models\User;
use App\Notifications\NewThreadReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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

        broadcast(new PostCreated($post))->toOthers();

        $recipientIds = $thread->posts()
            ->where('user_id', '!=', $request->user()->id)
            ->pluck('user_id')
            ->push($thread->user_id)
            ->filter(fn ($id) => $id && $id !== $request->user()->id)
            ->unique()
            ->values();

        if ($recipientIds->isNotEmpty()) {
            Notification::send(User::whereIn('id', $recipientIds)->get(), new NewThreadReply($post));
        }

        return redirect()->route('threads.show', [$forum->slug, $thread->slug]);
    }
}
