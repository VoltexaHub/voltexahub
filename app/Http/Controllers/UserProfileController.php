<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Contracts\View\View;

class UserProfileController extends Controller
{
    public function __invoke(User $user): View
    {
        $threadsCount = Thread::where('user_id', $user->id)->count();
        $postsCount = Post::where('user_id', $user->id)->count();

        $recentThreads = Thread::where('user_id', $user->id)
            ->with('forum:id,name,slug')
            ->latest()
            ->limit(10)
            ->get();

        $recentPosts = Post::where('user_id', $user->id)
            ->with(['thread:id,title,slug,forum_id', 'thread.forum:id,name,slug'])
            ->latest()
            ->limit(10)
            ->get();

        $isBlocked = false;
        $isFollowing = false;
        if ($viewer = request()->user()) {
            $isBlocked = \App\Models\UserBlock::where('blocker_id', $viewer->id)
                ->where('blocked_id', $user->id)
                ->exists();
            $isFollowing = \App\Models\Follow::where('follower_id', $viewer->id)
                ->where('followed_id', $user->id)
                ->exists();
        }

        $followerCount = \App\Models\Follow::where('followed_id', $user->id)->count();

        return view('theme::user-profile', compact('user', 'threadsCount', 'postsCount', 'recentThreads', 'recentPosts', 'isBlocked', 'isFollowing', 'followerCount'));
    }
}
