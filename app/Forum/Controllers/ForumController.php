<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Forum;
use App\Forum\Models\Thread;
use Inertia\Inertia;

class ForumController
{
    public function show(Forum $forum): \Inertia\Response
    {
        $threads = Thread::where('forum_id', $forum->id)
            ->where('is_deleted', false)
            ->with(['user.group', 'lastPost.user'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_post_id')
            ->paginate(25);

        return Inertia::render('Forum/Show', [
            'forum' => $forum->load('category'),
            'threads' => $threads,
        ]);
    }
}
