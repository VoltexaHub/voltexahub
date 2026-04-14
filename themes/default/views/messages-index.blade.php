@extends('theme::layout')

@section('title', 'Messages · '.config('app.name'))

@section('content')
    <header class="mb-8 flex items-end justify-between pb-5 border-b vx-hairline">
        <div>
            <p class="vx-meta mb-2">Inbox</p>
            <h1 class="vx-display text-4xl font-semibold tracking-tight vx-heading">Messages</h1>
        </div>
        <a href="{{ route('messages.create') }}" class="vx-btn-primary">Compose</a>
    </header>

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
                <a href="{{ route('messages.show', $conversation) }}" class="flex items-start gap-4 py-4 group">
                    @if($other)
                        <img src="{{ $other->avatar_url }}" alt="" class="w-11 h-11 rounded-full border vx-hairline shrink-0" />
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="vx-display font-medium vx-heading truncate">{{ $other?->name ?? '[deleted]' }}</span>
                            <span class="vx-meta normal-case tracking-normal text-[0.7rem] shrink-0">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                        </div>
                        @if($conversation->latestMessage)
                            <p class="text-sm {{ $isUnread ? 'vx-heading font-medium' : 'vx-muted' }} truncate mt-0.5">
                                @if($conversation->latestMessage->user_id === $me->id)<span class="vx-subtle">You ·</span> @endif
                                {{ Str::limit($conversation->latestMessage->body, 140) }}
                            </p>
                        @endif
                    </div>
                    @if($isUnread)
                        <span class="mt-2 w-2 h-2 rounded-full bg-[color:var(--accent)] shrink-0" aria-label="unread"></span>
                    @endif
                </a>
            </li>
        @empty
            <li class="py-16 text-center">
                <p class="vx-display text-xl vx-muted italic">No conversations.</p>
                <p class="vx-meta mt-3">Start one from any user's profile</p>
            </li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $conversations->links() }}</div>
@endsection
