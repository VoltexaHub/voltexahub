@extends('theme::layout')

@section('title', $user->name.' · '.config('app.name'))

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $user->name],
    ]])

    <header class="mb-10 flex items-center gap-6 pb-8 border-b vx-hairline">
        <img src="{{ $user->avatar_url }}" alt="" class="w-20 h-20 rounded-full border vx-hairline" />
        <div class="flex-1">
            <p class="vx-meta mb-1">Member since {{ $user->created_at->format('M Y') }}</p>
            <div class="flex items-center gap-3">
                <h1 class="vx-display text-4xl font-semibold tracking-tight vx-heading">{{ $user->name }}</h1>
                @if($user->is_admin)<span class="vx-chip">Admin</span>@endif
            </div>
            @auth
                @if(auth()->id() !== $user->id)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($isFollowing ?? false)
                            <form method="POST" action="{{ route('follows.destroy', $user) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="vx-btn-secondary text-xs py-1.5 px-3" style="color:var(--accent);border-color:var(--accent)">Following</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('follows.store', $user) }}">
                                @csrf
                                <button type="submit" class="vx-btn-primary text-xs py-1.5 px-3">Follow</button>
                            </form>
                        @endif
                        <a href="{{ route('messages.create', ['to' => $user->id]) }}" class="vx-btn-secondary text-xs py-1.5 px-3">Message</a>
                        @if($isBlocked ?? false)
                            <form method="POST" action="{{ route('blocks.destroy', $user) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="vx-btn-secondary text-xs py-1.5 px-3">Unblock</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('blocks.store', $user) }}" onsubmit="return confirm('Block {{ $user->name }}? You won\'t see their posts and they won\'t be able to message you.');">
                                @csrf
                                <button type="submit" class="vx-btn-secondary text-xs py-1.5 px-3" style="color:#991b1b;border-color:#fecaca">Block</button>
                            </form>
                        @endif
                    </div>
                @endif
            @endauth
        </div>
        <div class="hidden sm:grid grid-cols-2 gap-8 text-right shrink-0">
            <div>
                <div class="vx-display text-3xl font-semibold vx-heading tabular-nums">{{ $threadsCount }}</div>
                <div class="vx-meta">Threads</div>
            </div>
            <div>
                <div class="vx-display text-3xl font-semibold vx-heading tabular-nums">{{ $postsCount }}</div>
                <div class="vx-meta">Posts</div>
            </div>
        </div>
    </header>

    <div class="grid md:grid-cols-2 gap-10">
        <section>
            <h2 class="vx-meta mb-3 text-[color:var(--accent)]">Recent threads</h2>
            <ul class="vx-row-divide">
                @forelse($recentThreads as $thread)
                    <li class="py-3">
                        <a href="{{ route('threads.show', [$thread->forum->slug, $thread->slug]) }}" class="vx-display font-medium vx-heading hover:text-[color:var(--accent)] truncate block">{{ $thread->title }}</a>
                        <p class="vx-meta normal-case tracking-normal text-[0.7rem] mt-0.5">in {{ $thread->forum->name }} · {{ $thread->created_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="py-6 vx-muted text-sm italic">No threads yet.</li>
                @endforelse
            </ul>
        </section>

        <section>
            <h2 class="vx-meta mb-3 text-[color:var(--accent)]">Recent posts</h2>
            <ul class="vx-row-divide">
                @forelse($recentPosts as $post)
                    <li class="py-3">
                        <a href="{{ route('threads.show', [$post->thread->forum->slug, $post->thread->slug]) }}#post-{{ $post->id }}" class="vx-display font-medium vx-heading hover:text-[color:var(--accent)] truncate block">{{ $post->thread->title }}</a>
                        <p class="vx-meta normal-case tracking-normal text-[0.7rem] mt-0.5">in {{ $post->thread->forum->name }} · {{ $post->created_at->diffForHumans() }}</p>
                        <p class="vx-muted text-sm mt-1 line-clamp-2">{{ Str::limit($post->body, 180) }}</p>
                    </li>
                @empty
                    <li class="py-6 vx-muted text-sm italic">No posts yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
