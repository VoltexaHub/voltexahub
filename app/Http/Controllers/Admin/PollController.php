<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Poll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    public function index(Request $request): Response
    {
        $polls = Poll::query()
            ->with(['thread:id,title,slug,forum_id', 'thread.forum:id,name,slug'])
            ->withCount(['options', 'votes'])
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where('question', 'ilike', "%{$term}%"))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Polls/Index', [
            'polls' => $polls,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function edit(Poll $poll): Response
    {
        $poll->load([
            'thread:id,title,slug,forum_id',
            'thread.forum:id,name,slug',
            'options' => fn ($q) => $q->orderBy('position'),
        ]);

        return Inertia::render('Admin/Polls/Edit', [
            'poll' => $poll,
        ]);
    }

    public function update(Request $request, Poll $poll): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:250'],
            'allow_multiple' => ['required', 'boolean'],
            'closes_at' => ['nullable', 'date'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.id' => ['nullable', 'integer', 'exists:poll_options,id'],
            'options.*.text' => ['required', 'string', 'max:200'],
        ]);

        DB::transaction(function () use ($poll, $data) {
            $poll->update([
                'question' => $data['question'],
                'allow_multiple' => $data['allow_multiple'],
                'closes_at' => $data['closes_at'] ?? null,
            ]);

            $keptIds = [];
            foreach ($data['options'] as $i => $opt) {
                if (! empty($opt['id'])) {
                    $existing = $poll->options()->find($opt['id']);
                    if ($existing) {
                        $existing->update(['text' => $opt['text'], 'position' => $i]);
                        $keptIds[] = $existing->id;
                    }
                } else {
                    $new = $poll->options()->create([
                        'text' => $opt['text'],
                        'position' => $i,
                        'votes_count' => 0,
                    ]);
                    $keptIds[] = $new->id;
                }
            }

            $poll->options()->whereNotIn('id', $keptIds)->each(function ($opt) {
                $opt->votes()->delete();
                $opt->delete();
            });
        });

        AdminActivity::record('poll.update', $poll, $poll->question);

        return back()->with('flash.success', 'Poll updated.');
    }

    public function destroy(Poll $poll): RedirectResponse
    {
        AdminActivity::record('poll.delete', $poll, $poll->question);
        $poll->delete();

        return back()->with('flash.success', 'Poll deleted.');
    }
}
