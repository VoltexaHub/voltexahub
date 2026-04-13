<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use Illuminate\Contracts\View\View;

class ForumController extends Controller
{
    public function show(Forum $forum): View
    {
        $forum->load('category:id,name,slug');

        $threads = $forum->threads()
            ->with(['author:id,name', 'lastPost.author:id,name'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_post_at')
            ->paginate(20);

        return view('theme::forum-show', compact('forum', 'threads'));
    }
}
