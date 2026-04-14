@extends('theme::layout')

@section('title', ($q !== '' ? 'Search: '.$q : 'Search').' · '.config('app.name'))

@section('content')
    <header class="mb-8">
        <p class="vx-meta mb-2">The Hub · Search</p>
        <h1 class="vx-display text-4xl font-semibold tracking-tight vx-heading">
            @if($q !== '')Results for <em class="not-italic text-[color:var(--accent)]">"{{ $q }}"</em>@else Search @endif
        </h1>
    </header>

    <form method="GET" action="{{ route('search') }}" class="mb-10 flex gap-2 max-w-2xl">
        <input name="q" type="search" value="{{ $q }}" placeholder="Search threads and posts…" autofocus class="vx-input flex-1 text-base" />
        <button type="submit" class="vx-btn-primary">Search</button>
    </form>

    @if($q === '')
        <p class="vx-display text-xl vx-muted italic text-center py-16">What are you looking for?</p>
    @else
        @if($threads->isNotEmpty())
            <section class="mb-10">
                <h2 class="vx-meta mb-3 text-[color:var(--accent)]">Matching threads</h2>
                <ul class="vx-row-divide">
                    @foreach($threads as $thread)
                        <li class="py-3">
                            <a href="{{ route('threads.show', [$thread->forum->slug, $thread->slug]) }}" class="vx-display font-medium vx-heading hover:text-[color:var(--accent)]">{{ $thread->title }}</a>
                            <p class="vx-meta normal-case tracking-normal text-[0.7rem] mt-0.5">
                                in {{ $thread->forum->name }}
                                @if($thread->author) · by <a href="{{ route('users.show', $thread->author) }}" class="hover:text-[color:var(--accent)]">{{ $thread->author->name }}</a>@endif
                                · {{ $thread->created_at->diffForHumans() }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($posts && $posts->isNotEmpty())
            <section>
                <h2 class="vx-meta mb-3 text-[color:var(--accent)]">Matching posts · {{ $posts->total() }}</h2>
                <ul class="vx-row-divide">
                    @foreach($posts as $post)
                        <li class="py-4">
                            <a href="{{ route('threads.show', [$post->thread->forum->slug, $post->thread->slug]) }}#post-{{ $post->id }}" class="vx-display font-medium vx-heading hover:text-[color:var(--accent)]">{{ $post->thread->title }}</a>
                            <p class="vx-meta normal-case tracking-normal text-[0.7rem] mt-0.5">
                                in {{ $post->thread->forum->name }}
                                @if($post->author) · by <a href="{{ route('users.show', $post->author) }}" class="hover:text-[color:var(--accent)]">{{ $post->author->name }}</a>@endif
                                · {{ $post->created_at->diffForHumans() }}
                            </p>
                            <p class="vx-muted text-sm mt-2 line-clamp-2">{{ Str::limit(strip_tags($post->body), 240) }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
            <div class="mt-6">{{ $posts->links() }}</div>
        @elseif($threads->isEmpty())
            <p class="vx-display text-xl vx-muted italic text-center py-16">Nothing matches.</p>
        @endif
    @endif
@endsection
