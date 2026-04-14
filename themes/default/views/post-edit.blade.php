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

    <h1 class="text-2xl font-semibold vx-heading mb-5">Edit Post</h1>

    <form method="POST" action="{{ route('posts.update', $post->id) }}" class="vx-card p-5 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <textarea name="body" rows="10" required data-markdown class="vx-input">{{ old('body', $post->body) }}</textarea>
            @error('body')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('threads.show', [$post->thread->forum->slug, $post->thread->slug]) }}" class="vx-btn-secondary">Cancel</a>
            <button type="submit" class="vx-btn-primary">Save</button>
        </div>
    </form>
@endsection
