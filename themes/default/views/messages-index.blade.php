@extends('theme::layout')

@section('title', 'Messages · '.config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-semibold vx-heading">Messages</h1>
        <a href="{{ route('messages.create') }}" class="vx-btn-primary">New Message</a>
    </div>

    <div class="vx-card overflow-hidden">
        <ul class="vx-row-divide">
            @php $me = auth()->user(); @endphp
            @forelse($conversations as $conversation)
                @php
                    $other = $conversation->participants->firstWhere('id', '!=', $me->id);
                    $myPivot = $conversation->participants->firstWhere('id', $me->id)?->pivot;
                    $lastRead = $myPivot?->last_read_at;
                    $isUnread = $conversation->last_message_at && (! $lastRead || $conversation->last_message_at->gt($lastRead));
                @endphp
                <li>
                    <a href="{{ route('messages.show', $conversation) }}"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors {{ $isUnread ? 'bg-indigo-50/40 dark:bg-indigo-950/20' : '' }}">
                        @if($other)
                            <img src="{{ $other->avatar_url }}" alt="" class="w-10 h-10 rounded-full ring-1 ring-slate-200 dark:ring-slate-700" />
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="font-medium vx-heading truncate">{{ $other?->name ?? '[deleted user]' }}</span>
                                <span class="text-xs vx-subtle shrink-0">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                            </div>
                            @if($conversation->latestMessage)
                                <p class="text-sm vx-muted truncate {{ $isUnread ? 'font-semibold text-slate-700 dark:text-slate-200' : '' }}">
                                    @if($conversation->latestMessage->user_id === $me->id)<span class="vx-subtle">You:</span> @endif
                                    {{ Str::limit($conversation->latestMessage->body, 120) }}
                                </p>
                            @endif
                        </div>
                        @if($isUnread)
                            <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0" aria-label="unread"></span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="p-10 text-center vx-muted">No messages yet. Start a conversation.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">{{ $conversations->links() }}</div>
@endsection
