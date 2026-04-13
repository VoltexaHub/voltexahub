@extends('theme::layout')

@section('title', $forum->name.' · '.config('app.name'))

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $forum->category->name],
        ['label' => $forum->name],
    ]])

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $forum->name }}</h1>
            @if($forum->description)
                <p class="text-gray-500">{{ $forum->description }}</p>
            @endif
        </div>
        @auth
            <a href="{{ route('threads.create', $forum->slug) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">
                New Thread
            </a>
        @endauth
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <ul class="divide-y divide-gray-100">
            @forelse($threads as $thread)
                <li class="px-4 py-3 flex items-center gap-4 hover:bg-gray-50">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            @if($thread->is_pinned)<span class="text-xs font-semibold text-amber-600 uppercase">Pinned</span>@endif
                            @if($thread->is_locked)<span class="text-xs font-semibold text-red-600 uppercase">Locked</span>@endif
                            <a href="{{ route('threads.show', [$forum->slug, $thread->slug]) }}"
                               class="font-medium text-indigo-600 hover:underline truncate">{{ $thread->title }}</a>
                        </div>
                        <div class="text-xs text-gray-500">
                            by {{ $thread->author?->name }} · {{ $thread->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="hidden sm:block text-sm text-gray-500 w-24 text-center">
                        <div>{{ $thread->posts_count }} replies</div>
                        <div>{{ $thread->views_count }} views</div>
                    </div>
                    <div class="hidden md:block text-sm text-gray-500 w-48 truncate">
                        @if($thread->lastPost)
                            <div class="truncate">by {{ $thread->lastPost->author?->name }}</div>
                            <div class="text-xs">{{ $thread->last_post_at?->diffForHumans() }}</div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-gray-500">No threads yet. Be the first to post!</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">
        {{ $threads->links() }}
    </div>
@endsection
