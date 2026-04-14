<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\ThreadSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThreadSubscriptionController extends Controller
{
    public function mute(Request $request, Thread $thread): RedirectResponse
    {
        ThreadSubscription::updateOrCreate(
            ['user_id' => $request->user()->id, 'thread_id' => $thread->id],
            ['state' => ThreadSubscription::STATE_MUTED],
        );

        return back()->with('flash.success', 'Thread muted. You won\'t be notified of new replies.');
    }

    public function unmute(Request $request, Thread $thread): RedirectResponse
    {
        ThreadSubscription::where('user_id', $request->user()->id)
            ->where('thread_id', $thread->id)
            ->delete();

        return back()->with('flash.success', 'Thread unmuted.');
    }
}
