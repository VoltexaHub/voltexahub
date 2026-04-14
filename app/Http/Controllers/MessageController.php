<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with([
                'participants:id,name,email',
                'latestMessage.author:id,name',
            ])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('theme::messages-index', compact('conversations'));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorizeParticipant($request, $conversation);

        $conversation->load(['participants:id,name,email']);

        $messages = $conversation->messages()
            ->with('author:id,name,email')
            ->orderBy('created_at')
            ->paginate(50);

        $conversation->participants()
            ->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return view('theme::messages-show', compact('conversation', 'messages'));
    }

    public function create(Request $request): View
    {
        $to = null;
        if ($id = $request->integer('to')) {
            $to = User::find($id);
        }

        return view('theme::messages-create', compact('to'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'exists:users,id'],
            'body' => ['required', 'string', 'min:1', 'max:10000'],
        ]);

        $me = $request->user();
        $recipientId = (int) $data['recipient_id'];

        if ($recipientId === $me->id) {
            return back()->with('flash.error', "You can't message yourself.");
        }

        $conversation = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $me->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $recipientId))
            ->whereDoesntHave('participants', fn ($q) => $q->whereNotIn('users.id', [$me->id, $recipientId]))
            ->withCount('participants')
            ->having('participants_count', '=', 2)
            ->first();

        DB::transaction(function () use (&$conversation, $me, $recipientId, $data) {
            if (! $conversation) {
                $conversation = Conversation::create(['last_message_at' => now()]);
                $conversation->participants()->attach([$me->id, $recipientId]);
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $me->id,
                'body' => $data['body'],
            ]);

            $conversation->update(['last_message_at' => now()]);
            $conversation->participants()->updateExistingPivot($me->id, ['last_read_at' => now()]);
        });

        return redirect()->route('messages.show', $conversation);
    }

    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:10000'],
        ]);

        DB::transaction(function () use ($request, $conversation, $data) {
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'body' => $data['body'],
            ]);
            $conversation->update(['last_message_at' => now()]);
            $conversation->participants()
                ->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);
        });

        return redirect()->route('messages.show', $conversation);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(
            $conversation->participants()->where('users.id', $request->user()->id)->exists(),
            403,
        );
    }
}
