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

    <header class="mb-8">
        <p class="vx-meta mb-2">Posting to · {{ $forum->name }}</p>
        <h1 class="vx-display text-4xl font-semibold tracking-tight vx-heading">Start a new thread</h1>
    </header>

    <form method="POST" action="{{ route('threads.store', $forum->slug) }}" class="space-y-6 max-w-3xl">
        @csrf
        <div>
            <label class="vx-meta mb-2 block">Title</label>
            <input name="title" value="{{ old('title') }}" type="text" required
                   class="vx-input text-xl vx-display font-medium" style="padding:0.7rem 1rem;" placeholder="Give it a headline worth reading…" />
            @error('title')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="vx-meta mb-2 block">Body</label>
            <textarea name="body" rows="14" required data-markdown class="vx-input">{{ old('body') }}</textarea>
            @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('forums.show', $forum->slug) }}" class="vx-btn-secondary">Cancel</a>
            <button type="submit" class="vx-btn-primary">Publish Thread</button>
        </div>
    </form>
@endsection
