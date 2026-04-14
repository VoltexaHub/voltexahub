@extends('theme::layout')

@php
    $me = auth()->user();
    $other = $conversation->participants->firstWhere('id', '!=', $me->id);
@endphp

@section('title', ($other?->name ?? 'Conversation').' · '.config('app.name'))

@push('scripts')
    @vite(['resources/js/markdown-editor.js', 'resources/js/mentions.js'])
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Messages', 'url' => route('messages.index')],
        ['label' => $other?->name ?? 'Conversation'],
    ]])

    <header class="flex items-center gap-3 mb-8 pb-5 border-b vx-hairline">
        @if($other)
            <img src="{{ $other->avatar_url }}" alt="" class="w-12 h-12 rounded-full border vx-hairline" />
            <a href="{{ route('users.show', $other) }}" class="vx-display text-2xl font-semibold vx-heading hover:text-[color:var(--accent)]">{{ $other->name }}</a>
        @else
            <span class="vx-muted italic">[deleted user]</span>
        @endif
    </header>

    <div class="space-y-4 mb-10">
        @foreach($messages as $message)
            @php $mine = $message->user_id === $me->id; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%]">
                    <div class="vx-meta normal-case tracking-normal text-[0.7rem] mb-1 {{ $mine ? 'text-right' : '' }}">
                        {{ $message->author?->name ?? '[deleted]' }} · {{ $message->created_at->format('M j · g:i A') }}
                    </div>
                    @if($mine)
                        <div class="px-4 py-2.5 rounded-2xl rounded-br-sm vx-prose" style="background:var(--accent);color:#fff;">
                            <div style="color:#fff;">{!! $message->body_html !!}</div>
                        </div>
                    @else
                        <div class="px-4 py-2.5 rounded-2xl rounded-bl-sm vx-card vx-prose">
                            {!! $message->body_html !!}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mb-6">{{ $messages->links() }}</div>

    <form method="POST" action="{{ route('messages.reply', $conversation) }}" class="pt-6 border-t vx-hairline">
        @csrf
        <textarea name="body" rows="4" data-markdown required class="vx-input" placeholder="Type a reply in markdown…"></textarea>
        @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        <div class="mt-3 flex justify-end">
            <button type="submit" class="vx-btn-primary">Send</button>
        </div>
    </form>
@endsection
