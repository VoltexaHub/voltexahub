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

    <h1 class="text-2xl font-semibold text-gray-900 mb-4">Edit Post</h1>

    <form method="POST" action="{{ route('posts.update', $post->id) }}"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <textarea name="body" rows="10" required data-markdown
                      class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('body', $post->body) }}</textarea>
            @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('threads.show', [$post->thread->forum->slug, $post->thread->slug]) }}"
               class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">Save</button>
        </div>
    </form>
@endsection
