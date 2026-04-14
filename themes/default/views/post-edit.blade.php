@extends('theme::layout')

@section('title', 'Edit Post')

@push('scripts')
    @vite('resources/js/markdown-editor.js')
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $post->thread->forum->name, 'url' => route('forums.show', $post->thread->forum->slug)],
        ['label' => $post->thread->title, 'url' => route('threads.show', [$post->thread->forum->slug, $post->thread->slug])],
        ['label' => 'Edit Post'],
    ]])

    <header class="mb-6">
        <p class="vx-meta mb-2">Revising</p>
        <h1 class="vx-display text-4xl font-semibold tracking-tight vx-heading">Edit post</h1>
    </header>

    <form method="POST" action="{{ route('posts.update', $post->id) }}" class="space-y-4 max-w-3xl">
        @csrf
        @method('PUT')
        <textarea name="body" rows="12" required data-markdown class="vx-input">{{ old('body', $post->body) }}</textarea>
        @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        <div class="flex justify-end gap-2">
            <a href="{{ route('threads.show', [$post->thread->forum->slug, $post->thread->slug]) }}" class="vx-btn-secondary">Cancel</a>
            <button type="submit" class="vx-btn-primary">Save</button>
        </div>
    </form>
@endsection
