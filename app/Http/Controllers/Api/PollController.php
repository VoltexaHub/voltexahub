<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function store(Request $request, int $threadId): JsonResponse
    {
        $thread = Thread::findOrFail($threadId);
        $user = $request->user();

        if ($thread->user_id !== $user->id && ! $user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($thread->poll) {
            return response()->json(['message' => 'This thread already has a poll.'], 422);
        }

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'allow_multiple' => 'boolean',
            'closes_at' => 'nullable|date|after:now',
            'options' => 'required|array|min:2|max:10',
            'options.*' => 'required|string|max:255',
        ]);

        $poll = DB::transaction(function () use ($thread, $validated) {
            $poll = $thread->poll()->create([
                'question' => $validated['question'],
                'allow_multiple' => $validated['allow_multiple'] ?? false,
                'closes_at' => $validated['closes_at'] ?? null,
            ]);

            foreach ($validated['options'] as $index => $label) {
                $poll->options()->create([
                    'label' => $label,
                    'sort_order' => $index,
                ]);
            }

            return $poll;
        });

        $poll->load('options');

        return response()->json(['data' => $poll], 201);
    }

    public function show(Request $request, int $pollId): JsonResponse
    {
        $poll = Poll::with('options')->withCount('votes as total_votes')->findOrFail($pollId);

        $options = $poll->options->map(function ($option) use ($poll) {
            $voteCount = $option->votes()->count();
            return [
                'id' => $option->id,
                'label' => $option->label,
                'sort_order' => $option->sort_order,
                'vote_count' => $voteCount,
                'vote_percentage' => $poll->total_votes > 0
                    ? round(($voteCount / $poll->total_votes) * 100, 1)
                    : 0,
            ];
        });

        $userVotedOptionIds = [];
        if (auth()->check()) {
            $userVotedOptionIds = PollVote::where('poll_id', $poll->id)
                ->where('user_id', auth()->id())
                ->pluck('poll_option_id')
                ->toArray();
        }

        return response()->json([
            'data' => [
                'id' => $poll->id,
                'thread_id' => $poll->thread_id,
                'question' => $poll->question,
                'allow_multiple' => $poll->allow_multiple,
                'closes_at' => $poll->closes_at,
                'is_closed' => $poll->isClosed(),
                'total_votes' => $poll->total_votes,
                'options' => $options,
                'user_voted_option_ids' => $userVotedOptionIds,
            ],
        ]);
    }

    public function vote(Request $request, int $pollId): JsonResponse
    {
        $poll = Poll::findOrFail($pollId);
        $user = $request->user();

        if ($poll->isClosed()) {
            return response()->json(['message' => 'This poll is closed.'], 422);
        }

        $validated = $request->validate([
            'option_ids' => 'required|array|min:1',
            'option_ids.*' => 'required|integer|exists:poll_options,id',
        ]);

        // Verify all options belong to this poll
        $validOptionIds = $poll->options()->whereIn('id', $validated['option_ids'])->pluck('id');
        if ($validOptionIds->count() !== count($validated['option_ids'])) {
            return response()->json(['message' => 'Invalid option(s) for this poll.'], 422);
        }

        if (! $poll->allow_multiple && count($validated['option_ids']) > 1) {
            return response()->json(['message' => 'This poll only allows a single vote.'], 422);
        }

        DB::transaction(function () use ($poll, $user, $validated) {
            // Remove existing votes
            PollVote::where('poll_id', $poll->id)->where('user_id', $user->id)->delete();

            // Cast new votes
            foreach ($validated['option_ids'] as $optionId) {
                PollVote::create([
                    'poll_id' => $poll->id,
                    'poll_option_id' => $optionId,
                    'user_id' => $user->id,
                ]);
            }
        });

        return $this->show($request, $pollId);
    }

    public function removeVote(Request $request, int $pollId): JsonResponse
    {
        $poll = Poll::findOrFail($pollId);
        $user = $request->user();

        if ($poll->isClosed()) {
            return response()->json(['message' => 'This poll is closed.'], 422);
        }

        PollVote::where('poll_id', $poll->id)->where('user_id', $user->id)->delete();

        return $this->show($request, $pollId);
    }
}
