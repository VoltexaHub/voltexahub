@extends('theme::layout')

@section('title', 'Messages · '.config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold text-gray-900">Messages</h1>
        <a href="{{ route('messages.create') }}"
           class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">New Message</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <ul class="divide-y divide-gray-100">
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
                       class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 {{ $isUnread ? 'bg-indigo-50/40' : '' }}">
                        @if($other)
                            <img src="{{ $other->avatar_url }}" alt="" class="w-10 h-10 rounded-full" />
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="font-medium text-gray-900 truncate">{{ $other?->name ?? '[deleted user]' }}</span>
                                <span class="text-xs text-gray-400 shrink-0">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                            </div>
                            @if($conversation->latestMessage)
                                <p class="text-sm text-gray-600 truncate {{ $isUnread ? 'font-semibold' : '' }}">
                                    @if($conversation->latestMessage->user_id === $me->id)<span class="text-gray-400">You:</span> @endif
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
                <li class="p-8 text-center text-gray-500">No messages yet. Start a conversation.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">{{ $conversations->links() }}</div>
@endsection
