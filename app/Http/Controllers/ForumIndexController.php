<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Follow;
use App\Models\Thread;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ForumIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $feed = $request->input('feed') === 'following' && $request->user() ? 'following' : 'hub';

        $categories = Category::query()
            ->orderBy('position')
            ->with([
                'forums' => fn ($q) => $q->orderBy('position'),
                'forums.lastPost.author:id,name',
                'forums.lastPost.thread:id,title,slug,forum_id',
            ])
            ->get();

        $followingThreads = collect();
        if ($feed === 'following') {
            $ids = Follow::followingIds($request->user()->id);
            if (! empty($ids)) {
                $followingThreads = Thread::query()
                    ->whereIn('user_id', $ids)
                    ->with(['author:id,name,email', 'forum:id,name,slug', 'lastPost.author:id,name,email'])
                    ->orderByDesc('last_post_at')
                    ->limit(25)
                    ->get();
            }
        }

        return view('theme::forum-index', compact('categories', 'feed', 'followingThreads'));
    }
}
