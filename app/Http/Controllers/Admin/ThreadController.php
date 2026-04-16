<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Forum;
use App\Models\Thread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ThreadController extends Controller
{
    public function index(Request $request): Response
    {
        $threads = Thread::query()
            ->with(['author:id,name', 'forum:id,name,slug'])
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where('title', 'ilike', "%{$term}%"))
            ->orderByDesc('last_post_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Threads/Index', [
            'threads' => $threads,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function edit(Thread $thread): Response
    {
        $thread->load('forum:id,name,slug');

        return Inertia::render('Admin/Threads/Edit', [
            'thread' => $thread,
            'forums' => Forum::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Thread $thread): RedirectResponse
    {
        $data = $request->validate([
            'is_pinned' => ['sometimes', 'boolean'],
            'is_locked' => ['sometimes', 'boolean'],
            'forum_id' => ['sometimes', 'exists:forums,id'],
            'title' => ['sometimes', 'string', 'min:3', 'max:200'],
            'slug' => ['sometimes', 'string', 'max:220', 'alpha_dash'],
        ]);

        if (isset($data['slug'])) {
            $conflict = Thread::where('forum_id', $data['forum_id'] ?? $thread->forum_id)
                ->where('slug', $data['slug'])
                ->where('id', '!=', $thread->id)
                ->exists();
            if ($conflict) {
                return back()->withErrors(['slug' => 'Another thread in this forum already uses that slug.']);
            }
        }

        if (isset($data['forum_id']) && $data['forum_id'] !== $thread->forum_id) {
            $oldForum = $thread->forum;
            $newForum = Forum::find($data['forum_id']);
            $postsMoving = $thread->posts_count;

            $thread->update(['forum_id' => $newForum->id]);

            $oldForum->decrement('threads_count');
            $oldForum->decrement('posts_count', $postsMoving);
            $newForum->increment('threads_count');
            $newForum->increment('posts_count', $postsMoving);
        }

        $thread->update(collect($data)->except('forum_id')->all());

        return back()->with('flash.success', 'Thread updated.');
    }

    public function destroy(Thread $thread): RedirectResponse
    {
        AdminActivity::record('thread.delete', $thread, $thread->title);
        $forum = $thread->forum;
        $postsRemoved = $thread->posts_count;
        $thread->delete();

        $forum->decrement('threads_count');
        $forum->decrement('posts_count', $postsRemoved);

        return back()->with('flash.success', 'Thread deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:threads,id'],
        ]);

        $threads = Thread::whereIn('id', $data['ids'])
            ->get(['id', 'title', 'forum_id', 'posts_count']);

        AdminActivity::record('thread.bulk-delete', null, count($threads).' threads',
            ['ids' => $threads->pluck('id')->all(), 'titles' => $threads->pluck('title')->all()]);

        DB::transaction(function () use ($threads) {
            $perForum = $threads->groupBy('forum_id')->map(fn ($group) => [
                'threads' => $group->count(),
                'posts' => (int) $group->sum('posts_count'),
            ]);

            Thread::whereIn('id', $threads->pluck('id'))->delete();

            foreach ($perForum as $forumId => $counts) {
                Forum::where('id', $forumId)->update([
                    'threads_count' => DB::raw('GREATEST(threads_count - '.$counts['threads'].', 0)'),
                    'posts_count' => DB::raw('GREATEST(posts_count - '.$counts['posts'].', 0)'),
                ]);
            }
        });

        $n = $threads->count();
        return back()->with('flash.success', "Deleted {$n} thread".($n === 1 ? '' : 's').'.');
    }
}
