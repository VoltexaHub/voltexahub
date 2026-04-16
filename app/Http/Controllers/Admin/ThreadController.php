<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Forum;
use App\Models\Thread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

            $thread->update(['forum_id' => $newForum->id]);

            $oldForum->update([
                'threads_count' => $oldForum->threads()->count(),
                'posts_count' => $oldForum->threads()->sum('posts_count'),
            ]);
            $newForum->update([
                'threads_count' => $newForum->threads()->count(),
                'posts_count' => $newForum->threads()->sum('posts_count'),
            ]);
        }

        $thread->update(collect($data)->except('forum_id')->all());

        return back()->with('flash.success', 'Thread updated.');
    }

    public function destroy(Thread $thread): RedirectResponse
    {
        AdminActivity::record('thread.delete', $thread, $thread->title);
        $forum = $thread->forum;
        $thread->delete();

        $forum->update([
            'threads_count' => $forum->threads()->count(),
            'posts_count' => $forum->threads()->sum('posts_count'),
        ]);

        return back()->with('flash.success', 'Thread deleted.');
    }
}
