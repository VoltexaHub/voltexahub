@extends('theme::layout')

@section('title', 'New Message · '.config('app.name'))

@push('scripts')
    @vite('resources/js/markdown-editor.js')
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Messages', 'url' => route('messages.index')],
        ['label' => 'New Message'],
    ]])

    <h1 class="text-2xl font-semibold vx-heading mb-5">New Message</h1>

    <form method="POST" action="{{ route('messages.store') }}" class="vx-card p-5 space-y-4 max-w-2xl">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">To</label>
            @if($to)
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 ring-1 ring-slate-200 dark:ring-slate-700 rounded-lg px-3 py-2">
                    <img src="{{ $to->avatar_url }}" alt="" class="w-6 h-6 rounded-full" />
                    <span class="font-medium vx-heading">{{ $to->name }}</span>
                    <input type="hidden" name="recipient_id" value="{{ $to->id }}" />
                </div>
            @else
                <input name="recipient_id" type="number" placeholder="User ID" required class="vx-input" />
                <p class="text-xs vx-subtle mt-1">Tip: open a user's profile and click the "Send Message" button to skip this step.</p>
            @endif
            @error('recipient_id')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Message</label>
            <textarea name="body" rows="8" data-markdown required class="vx-input">{{ old('body') }}</textarea>
            @error('body')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('messages.index') }}" class="vx-btn-secondary">Cancel</a>
            <button type="submit" class="vx-btn-primary">Send</button>
        </div>
    </form>
@endsection
