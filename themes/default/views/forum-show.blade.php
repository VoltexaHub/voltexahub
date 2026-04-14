@extends('theme::layout')

@section('title', $forum->name.' · '.config('app.name'))

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $forum->category->name],
        ['label' => $forum->name],
    ]])

    <header class="mb-8 pb-5 border-b vx-hairline flex items-end justify-between gap-6">
        <div class="min-w-0">
            <p class="vx-meta mb-1.5">{{ $forum->category->name }}</p>
            <h1 class="vx-display text-4xl font-semibold tracking-tight vx-heading">{{ $forum->name }}</h1>
            @if($forum->description)
                <p class="vx-muted mt-2 max-w-2xl">{{ $forum->description }}</p>
            @endif
        </div>
        @auth
            <a href="{{ route('threads.create', $forum->slug) }}" class="vx-btn-primary shrink-0">
                New Thread
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </a>
        @endauth
    </header>

    <ul class="vx-row-divide">
        @forelse($threads as $thread)
            <li class="py-5 flex items-start gap-6 group">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($thread->is_pinned)<span class="vx-chip">Pinned</span>@endif
                        @if($thread->is_locked)<span class="vx-chip" style="color:#991b1b;background:#fee2e2;border-color:#fecaca;">Locked</span>@endif
                        <a href="{{ route('threads.show', [$forum->slug, $thread->slug]) }}"
                           class="vx-display text-lg font-medium vx-heading hover:text-[color:var(--accent)] transition-colors">
                            {{ $thread->title }}
                        </a>
                    </div>
                    <p class="vx-meta mt-1 normal-case tracking-normal text-[0.72rem]">
                        by @if($thread->author)<a href="{{ route('users.show', $thread->author) }}" class="hover:text-[color:var(--accent)] vx-muted">{{ $thread->author->name }}</a>@else [deleted] @endif
                        <span class="opacity-60 mx-1">·</span>
                        {{ $thread->created_at->diffForHumans() }}
                    </p>
                </div>
                <div class="hidden sm:flex items-baseline gap-4 w-28 shrink-0 justify-end">
                    <div class="text-right">
                        <div class="vx-display font-medium vx-heading tabular-nums">{{ $thread->posts_count }}</div>
                        <div class="vx-meta">replies</div>
                    </div>
                    <div class="text-right vx-subtle">
                        <div class="font-mono text-sm tabular-nums">{{ $thread->views_count }}</div>
                        <div class="vx-meta">views</div>
                    </div>
                </div>
                <div class="hidden md:block w-44 shrink-0 text-right">
                    @if($thread->lastPost)
                        <p class="text-sm vx-heading truncate">
                            @if($thread->lastPost->author)<a href="{{ route('users.show', $thread->lastPost->author) }}" class="hover:text-[color:var(--accent)]">{{ $thread->lastPost->author->name }}</a>@else [deleted] @endif
                        </p>
                        <p class="vx-meta mt-0.5 normal-case tracking-normal text-[0.7rem]">{{ $thread->last_post_at?->diffForHumans() }}</p>
                    @endif
                </div>
            </li>
        @empty
            <li class="py-16 text-center">
                <p class="vx-display text-xl vx-muted italic">The quiet before the first post.</p>
                @auth<p class="vx-meta mt-3">Be the first</p>@endauth
            </li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $threads->links() }}</div>
@endsection
