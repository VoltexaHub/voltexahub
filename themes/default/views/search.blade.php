@extends('theme::layout')

@section('title', ($q !== '' ? 'Search: '.$q : 'Search').' · '.config('app.name'))

@section('content')
    <form method="GET" action="{{ route('search') }}" class="mb-6 flex gap-2">
        <input name="q" type="search" value="{{ $q }}" placeholder="Search threads and posts..." autofocus class="vx-input flex-1" />
        <button type="submit" class="vx-btn-primary">Search</button>
    </form>

    @if($q === '')
        <p class="text-center vx-muted py-12">Enter a query to search.</p>
    @else
        <h1 class="text-xl font-semibold vx-heading mb-4">
            Results for <span class="text-indigo-600 dark:text-indigo-400">{{ $q }}</span>
        </h1>

        @if($threads->isNotEmpty())
            <section class="vx-card overflow-hidden mb-6">
                <header class="vx-card-header"><h2 class="font-semibold vx-heading">Matching threads</h2></header>
                <ul class="vx-row-divide">
                    @foreach($threads as $thread)
                        <li class="px-4 py-3">
                            <a href="{{ route('threads.show', [$thread->forum->slug, $thread->slug]) }}" class="vx-link font-medium">{{ $thread->title }}</a>
                            <div class="text-xs vx-subtle">
                                in {{ $thread->forum->name }}
                                @if($thread->author) · by <a href="{{ route('users.show', $thread->author) }}" class="hover:text-indigo-500">{{ $thread->author->name }}</a>@endif
                                · {{ $thread->created_at->diffForHumans() }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($posts && $posts->isNotEmpty())
            <section class="vx-card overflow-hidden">
                <header class="vx-card-header"><h2 class="font-semibold vx-heading">Matching posts ({{ $posts->total() }})</h2></header>
                <ul class="vx-row-divide">
                    @foreach($posts as $post)
                        <li class="px-4 py-3">
                            <a href="{{ route('threads.show', [$post->thread->forum->slug, $post->thread->slug]) }}#post-{{ $post->id }}" class="vx-link font-medium">{{ $post->thread->title }}</a>
                            <div class="text-xs vx-subtle">
                                in {{ $post->thread->forum->name }}
                                @if($post->author) · by <a href="{{ route('users.show', $post->author) }}" class="hover:text-indigo-500">{{ $post->author->name }}</a>@endif
                                · {{ $post->created_at->diffForHumans() }}
                            </div>
                            <p class="text-sm vx-muted mt-1 line-clamp-2">{{ Str::limit(strip_tags($post->body), 220) }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
            <div class="mt-4">{{ $posts->links() }}</div>
        @elseif($threads->isEmpty())
            <p class="text-center vx-muted py-12">No matches found.</p>
        @endif
    @endif
@endsection
