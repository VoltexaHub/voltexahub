<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Forum;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = Post::query()
            ->with([
                'author:id,name',
                'thread:id,title,slug,forum_id',
                'thread.forum:id,name,slug',
            ])
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where('body', 'ilike', "%{$term}%"))
            ->when($request->integer('forum_id'), fn ($q, $id) => $q->whereHas('thread', fn ($t) => $t->where('forum_id', $id)))
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
            'forums' => Forum::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'forum_id' => $request->integer('forum_id') ?: null,
                'user_id' => $request->integer('user_id') ?: null,
            ],
        ]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $thread = $post->thread;
        AdminActivity::record('post.delete', $post, 'Post #'.$post->id.' in "'.($thread?->title ?? '?').'"');
        $post->delete();

        $thread?->update(['posts_count' => $thread->posts()->count()]);
        $thread?->forum?->update(['posts_count' => $thread->forum->threads()->sum('posts_count')]);

        return back()->with('flash.success', 'Post deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:posts,id'],
        ]);

        $posts = Post::with('thread.forum')->whereIn('id', $data['ids'])->get();
        $threadIds = $posts->pluck('thread_id')->unique();
        $forumIds = $posts->pluck('thread.forum_id')->unique()->filter();

        AdminActivity::record('post.bulk-delete', null, count($data['ids']).' posts', ['ids' => $data['ids']]);
        Post::whereIn('id', $data['ids'])->delete();

        foreach ($threadIds as $tid) {
            $thread = $posts->firstWhere('thread_id', $tid)->thread;
            $thread?->update(['posts_count' => $thread->posts()->count()]);
        }
        foreach ($forumIds as $fid) {
            $forum = Forum::find($fid);
            $forum?->update(['posts_count' => $forum->threads()->sum('posts_count')]);
        }

        $count = count($data['ids']);
        return back()->with('flash.success', "Deleted {$count} post".($count === 1 ? '' : 's').'.');
    }
}
