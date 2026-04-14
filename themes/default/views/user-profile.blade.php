@extends('theme::layout')

@section('title', $user->name.' · '.config('app.name'))

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $user->name],
    ]])

    <div class="vx-card p-6 mb-6 flex items-center gap-6">
        <img src="{{ $user->avatar_url }}" alt="" class="w-20 h-20 rounded-full ring-1 ring-slate-200 dark:ring-slate-700" />
        <div class="flex-1">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold vx-heading">{{ $user->name }}</h1>
                @if($user->is_admin)
                    <span class="inline-block px-2 py-0.5 text-xs rounded-md bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-200/60 dark:ring-indigo-900/60">Admin</span>
                @endif
            </div>
            <p class="text-sm vx-muted mt-1">Joined {{ $user->created_at->format('F j, Y') }} · {{ $user->created_at->diffForHumans() }}</p>
            @auth
                @if(auth()->id() !== $user->id)
                    <a href="{{ route('messages.create', ['to' => $user->id]) }}" class="inline-block mt-3 vx-btn-primary text-xs py-1.5 px-3">Send Message</a>
                @endif
            @endauth
        </div>
        <div class="grid grid-cols-2 gap-6 text-center">
            <div>
                <div class="text-2xl font-semibold vx-heading tabular-nums">{{ $threadsCount }}</div>
                <div class="text-xs vx-subtle uppercase tracking-wide">Threads</div>
            </div>
            <div>
                <div class="text-2xl font-semibold vx-heading tabular-nums">{{ $postsCount }}</div>
                <div class="text-xs vx-subtle uppercase tracking-wide">Posts</div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <section class="vx-card overflow-hidden">
            <header class="vx-card-header"><h2 class="font-semibold vx-heading">Recent threads</h2></header>
            <ul class="vx-row-divide">
                @forelse($recentThreads as $thread)
                    <li class="px-4 py-3">
                        <a href="{{ route('threads.show', [$thread->forum->slug, $thread->slug]) }}" class="vx-link font-medium truncate block">{{ $thread->title }}</a>
                        <div class="text-xs vx-subtle">in {{ $thread->forum->name }} · {{ $thread->created_at->diffForHumans() }}</div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center vx-muted text-sm">No threads yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="vx-card overflow-hidden">
            <header class="vx-card-header"><h2 class="font-semibold vx-heading">Recent posts</h2></header>
            <ul class="vx-row-divide">
                @forelse($recentPosts as $post)
                    <li class="px-4 py-3">
                        <a href="{{ route('threads.show', [$post->thread->forum->slug, $post->thread->slug]) }}#post-{{ $post->id }}" class="vx-link font-medium truncate block">{{ $post->thread->title }}</a>
                        <div class="text-xs vx-subtle">in {{ $post->thread->forum->name }} · {{ $post->created_at->diffForHumans() }}</div>
                        <p class="text-sm vx-muted mt-1 line-clamp-2">{{ Str::limit($post->body, 160) }}</p>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center vx-muted text-sm">No posts yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
