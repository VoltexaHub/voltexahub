@extends('theme::layout')

@section('title', 'New thread in '.$forum->name)

@push('scripts')
    @vite('resources/js/markdown-editor.js')
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $forum->name, 'url' => route('forums.show', $forum->slug)],
        ['label' => 'New Thread'],
    ]])

    <h1 class="text-2xl font-semibold vx-heading mb-5">New Thread in {{ $forum->name }}</h1>

    <form method="POST" action="{{ route('threads.store', $forum->slug) }}" class="vx-card p-5 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title</label>
            <input name="title" value="{{ old('title') }}" type="text" required class="vx-input" />
            @error('title')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Body</label>
            <textarea name="body" rows="10" required data-markdown class="vx-input">{{ old('body') }}</textarea>
            @error('body')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('forums.show', $forum->slug) }}" class="vx-btn-secondary">Cancel</a>
            <button type="submit" class="vx-btn-primary">Create Thread</button>
        </div>
    </form>
@endsection
