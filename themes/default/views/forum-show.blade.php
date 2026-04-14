@extends('theme::layout')

@section('title', $forum->name.' · '.config('app.name'))

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $forum->category->name],
        ['label' => $forum->name],
    ]])

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-semibold vx-heading">{{ $forum->name }}</h1>
            @if($forum->description)
                <p class="vx-muted mt-1">{{ $forum->description }}</p>
            @endif
        </div>
        @auth
            <a href="{{ route('threads.create', $forum->slug) }}" class="vx-btn-primary">New Thread</a>
        @endauth
    </div>

    <div class="vx-card overflow-hidden">
        <ul class="vx-row-divide">
            @forelse($threads as $thread)
                <li class="px-4 py-3 flex items-center gap-4 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            @if($thread->is_pinned)<span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wide">Pinned</span>@endif
                            @if($thread->is_locked)<span class="text-[10px] font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide">Locked</span>@endif
                            <a href="{{ route('threads.show', [$forum->slug, $thread->slug]) }}"
                               class="font-medium vx-link truncate">{{ $thread->title }}</a>
                        </div>
                        <div class="text-xs vx-subtle mt-0.5">
                            by @if($thread->author)<a href="{{ route('users.show', $thread->author) }}" class="hover:text-indigo-500">{{ $thread->author->name }}</a>@else [deleted] @endif · {{ $thread->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="hidden sm:block text-sm vx-muted w-24 text-center tabular-nums">
                        <div>{{ $thread->posts_count }} replies</div>
                        <div>{{ $thread->views_count }} views</div>
                    </div>
                    <div class="hidden md:block text-sm vx-muted w-48 truncate">
                        @if($thread->lastPost)
                            <div class="truncate">by @if($thread->lastPost->author)<a href="{{ route('users.show', $thread->lastPost->author) }}" class="hover:text-indigo-500">{{ $thread->lastPost->author->name }}</a>@else [deleted] @endif</div>
                            <div class="text-xs vx-subtle">{{ $thread->last_post_at?->diffForHumans() }}</div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="px-4 py-10 text-center vx-muted">No threads yet. Be the first to post!</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">{{ $threads->links() }}</div>
@endsection
