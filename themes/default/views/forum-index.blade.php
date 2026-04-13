@extends('theme::layout')

@section('title', 'Forums · '.config('app.name'))

@section('content')
    <div class="space-y-6">
        @foreach($categories as $cat)
            <section class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <header class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-800">{{ $cat->name }}</h2>
                    @if($cat->description)
                        <p class="text-sm text-gray-500">{{ $cat->description }}</p>
                    @endif
                </header>
                <ul class="divide-y divide-gray-100">
                    @forelse($cat->forums as $forum)
                        <li class="px-4 py-3 flex items-center gap-4 hover:bg-gray-50">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('forums.show', $forum->slug) }}" class="font-medium text-indigo-600 hover:underline">
                                    {{ $forum->name }}
                                </a>
                                @if($forum->description)
                                    <p class="text-sm text-gray-500 truncate">{{ $forum->description }}</p>
                                @endif
                            </div>
                            <div class="hidden sm:block text-sm text-gray-500 w-32 text-center">
                                <div>{{ $forum->threads_count }} threads</div>
                                <div>{{ $forum->posts_count }} posts</div>
                            </div>
                            <div class="hidden md:block text-sm text-gray-500 w-56 truncate">
                                @if($forum->lastPost && $forum->lastPost->thread)
                                    <a href="{{ route('threads.show', [$forum->slug, $forum->lastPost->thread->slug]) }}" class="text-gray-700 hover:underline truncate block">
                                        {{ $forum->lastPost->thread->title }}
                                    </a>
                                    <div class="text-xs">
                                        by {{ $forum->lastPost->author?->name }} · {{ $forum->last_post_at?->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-gray-400">No posts yet</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-gray-500 text-sm">No forums in this category.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
@endsection
