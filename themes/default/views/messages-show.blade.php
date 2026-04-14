@extends('theme::layout')

@php
    $me = auth()->user();
    $other = $conversation->participants->firstWhere('id', '!=', $me->id);
@endphp

@section('title', ($other?->name ?? 'Conversation').' · '.config('app.name'))

@push('scripts')
    @vite('resources/js/markdown-editor.js')
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Messages', 'url' => route('messages.index')],
        ['label' => $other?->name ?? 'Conversation'],
    ]])

    <div class="flex items-center gap-3 mb-5">
        @if($other)
            <img src="{{ $other->avatar_url }}" alt="" class="w-10 h-10 rounded-full ring-1 ring-slate-200 dark:ring-slate-700" />
            <a href="{{ route('users.show', $other) }}" class="text-lg font-semibold vx-heading hover:text-indigo-500">{{ $other->name }}</a>
        @else
            <span class="vx-muted">[deleted user]</span>
        @endif
    </div>

    <div class="space-y-3 mb-6">
        @foreach($messages as $message)
            @php $mine = $message->user_id === $me->id; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%]">
                    <div class="text-xs vx-subtle mb-1 {{ $mine ? 'text-right' : '' }}">
                        {{ $message->author?->name ?? '[deleted]' }} · {{ $message->created_at->format('M j, Y g:i A') }}
                    </div>
                    <div class="px-4 py-2.5 rounded-2xl prose prose-sm max-w-none shadow-sm
                                {{ $mine
                                    ? 'bg-indigo-600 text-white prose-invert rounded-br-sm'
                                    : 'bg-white dark:bg-slate-900 ring-1 ring-slate-200/70 dark:ring-slate-800/70 dark:prose-invert rounded-bl-sm' }}">
                        {!! $message->body_html !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mb-4">{{ $messages->links() }}</div>

    <form method="POST" action="{{ route('messages.reply', $conversation) }}" class="vx-card p-4">
        @csrf
        <textarea name="body" rows="4" data-markdown required class="vx-input" placeholder="Type a reply in markdown..."></textarea>
        @error('body')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
        <div class="mt-3 flex justify-end">
            <button type="submit" class="vx-btn-primary">Send</button>
        </div>
    </form>
@endsection
