<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use App\Models\ThreadSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThreadSubscriptionController extends Controller
{
    public function toggle(Request $request, int $id): JsonResponse
    {
        $thread = Thread::findOrFail($id);
        $user = $request->user();

        $existing = ThreadSubscription::where('user_id', $user->id)
            ->where('thread_id', $thread->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'data' => ['subscribed' => false],
                'message' => 'Unsubscribed from thread.',
            ]);
        }

        ThreadSubscription::create([
            'user_id' => $user->id,
            'thread_id' => $thread->id,
        ]);

        return response()->json([
            'data' => ['subscribed' => true],
            'message' => 'Subscribed to thread.',
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $thread = Thread::findOrFail($id);
        $user = $request->user();

        $subscribed = ThreadSubscription::where('user_id', $user->id)
            ->where('thread_id', $thread->id)
            ->exists();

        return response()->json([
            'data' => ['subscribed' => $subscribed],
        ]);
    }
}
