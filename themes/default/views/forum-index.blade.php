@extends('theme::layout')

@section('title', 'Forums · '.config('app.name'))

@section('content')
    <header class="mb-10 flex items-baseline justify-between">
        <div>
            <p class="vx-meta mb-2">The Hub</p>
            <h1 class="vx-display text-4xl md:text-5xl font-semibold tracking-tight vx-heading">
                Forums
            </h1>
        </div>
        <p class="vx-meta hidden md:block">
            {{ $categories->sum(fn($c) => $c->forums->count()) }} forums · {{ $categories->count() }} categories
        </p>
    </header>

    <div class="space-y-12">
        @foreach($categories as $cat)
            <section>
                <header class="flex items-baseline gap-4 mb-4 pb-3 border-b vx-hairline">
                    <span class="vx-meta text-[color:var(--accent)]">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h2 class="vx-display text-2xl font-semibold tracking-tight vx-heading">{{ $cat->name }}</h2>
                    @if($cat->description)
                        <p class="vx-muted text-sm ml-2 hidden sm:block">— {{ $cat->description }}</p>
                    @endif
                </header>
                <ul class="vx-row-divide">
                    @forelse($cat->forums as $forum)
                        <li class="py-5 flex items-start gap-6 group">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('forums.show', $forum->slug) }}" class="vx-display text-lg font-medium vx-heading hover:text-[color:var(--accent)] transition-colors">
                                    {{ $forum->name }}
                                </a>
                                @if($forum->description)
                                    <p class="vx-muted text-sm mt-0.5 max-w-xl">{{ $forum->description }}</p>
                                @endif
                            </div>
                            <div class="hidden sm:flex flex-col items-end w-28 shrink-0">
                                <span class="vx-display text-lg font-medium vx-heading tabular-nums">{{ $forum->threads_count }}</span>
                                <span class="vx-meta">threads</span>
                                <span class="vx-display text-lg font-medium vx-heading tabular-nums mt-1">{{ $forum->posts_count }}</span>
                                <span class="vx-meta">posts</span>
                            </div>
                            <div class="hidden md:block w-56 shrink-0 text-sm">
                                @if($forum->lastPost && $forum->lastPost->thread)
                                    <a href="{{ route('threads.show', [$forum->slug, $forum->lastPost->thread->slug]) }}" class="vx-heading hover:text-[color:var(--accent)] truncate block">
                                        {{ $forum->lastPost->thread->title }}
                                    </a>
                                    <p class="vx-meta mt-1 normal-case tracking-normal text-[0.7rem]">
                                        <span class="opacity-70">by</span>
                                        @if($forum->lastPost->author)<a href="{{ route('users.show', $forum->lastPost->author) }}" class="hover:text-[color:var(--accent)]">{{ $forum->lastPost->author->name }}</a>@else [deleted] @endif
                                        <span class="opacity-60 mx-1">·</span>
                                        <span>{{ $forum->last_post_at?->diffForHumans() }}</span>
                                    </p>
                                @else
                                    <p class="vx-subtle italic text-xs">Silent so far</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center vx-muted text-sm italic">No forums in this category.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
@endsection
