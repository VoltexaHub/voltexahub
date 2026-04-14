@extends('theme::layout')

@section('title', 'Forums · '.config('app.name'))

@section('content')
    <div class="space-y-6">
        @foreach($categories as $cat)
            <section class="vx-card overflow-hidden">
                <header class="vx-card-header">
                    <h2 class="font-semibold vx-heading">{{ $cat->name }}</h2>
                    @if($cat->description)
                        <p class="text-sm vx-muted">{{ $cat->description }}</p>
                    @endif
                </header>
                <ul class="vx-row-divide">
                    @forelse($cat->forums as $forum)
                        <li class="px-4 py-4 flex items-center gap-4 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('forums.show', $forum->slug) }}" class="font-medium vx-link">
                                    {{ $forum->name }}
                                </a>
                                @if($forum->description)
                                    <p class="text-sm vx-muted truncate">{{ $forum->description }}</p>
                                @endif
                            </div>
                            <div class="hidden sm:block text-sm vx-muted w-32 text-center tabular-nums">
                                <div>{{ $forum->threads_count }} threads</div>
                                <div>{{ $forum->posts_count }} posts</div>
                            </div>
                            <div class="hidden md:block text-sm vx-muted w-56 truncate">
                                @if($forum->lastPost && $forum->lastPost->thread)
                                    <a href="{{ route('threads.show', [$forum->slug, $forum->lastPost->thread->slug]) }}" class="text-slate-700 dark:text-slate-300 hover:vx-heading truncate block">
                                        {{ $forum->lastPost->thread->title }}
                                    </a>
                                    <div class="text-xs vx-subtle">
                                        by @if($forum->lastPost->author)<a href="{{ route('users.show', $forum->lastPost->author) }}" class="hover:text-indigo-500">{{ $forum->lastPost->author->name }}</a>@else [deleted] @endif · {{ $forum->last_post_at?->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="vx-subtle">No posts yet</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center vx-muted text-sm">No forums in this category.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
@endsection
