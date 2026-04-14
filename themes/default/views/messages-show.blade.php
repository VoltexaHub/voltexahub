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

    <div class="flex items-center gap-3 mb-4">
        @if($other)
            <img src="{{ $other->avatar_url }}" alt="" class="w-10 h-10 rounded-full" />
            <a href="{{ route('users.show', $other) }}" class="text-lg font-semibold text-gray-900 hover:text-indigo-600">{{ $other->name }}</a>
        @else
            <span class="text-gray-500">[deleted user]</span>
        @endif
    </div>

    <div class="space-y-3 mb-6">
        @foreach($messages as $message)
            @php $mine = $message->user_id === $me->id; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%]">
                    <div class="text-xs text-gray-500 mb-1 {{ $mine ? 'text-right' : '' }}">
                        {{ $message->author?->name ?? '[deleted]' }} · {{ $message->created_at->format('M j, Y g:i A') }}
                    </div>
                    <div class="px-4 py-2 rounded-lg shadow-sm border prose prose-sm max-w-none
                                {{ $mine ? 'bg-indigo-600 text-white border-indigo-600 prose-invert' : 'bg-white border-gray-200' }}">
                        {!! $message->body_html !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mb-4">{{ $messages->links() }}</div>

    <form method="POST" action="{{ route('messages.reply', $conversation) }}"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        @csrf
        <textarea name="body" rows="4" data-markdown required
                  class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="Type a reply in markdown..."></textarea>
        @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        <div class="mt-3 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">Send</button>
        </div>
    </form>
@endsection
