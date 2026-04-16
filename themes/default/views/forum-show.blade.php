@extends('theme::layout')

@section('title', $forum->name.' · '.config('app.name'))

@push('head')
    @php
        $forumDesc = $forum->description
            ?: "Latest discussions in {$forum->name} on ".config('app.name').'.';
        $forumUrl = route('forums.show', $forum->slug);
    @endphp
    <meta name="description" content="{{ $forumDesc }}">
    <meta property="og:title" content="{{ $forum->name }}" />
    <meta property="og:description" content="{{ $forumDesc }}" />
    <meta property="og:url" content="{{ $forumUrl }}" />
    <meta property="og:site_name" content="{{ config('app.name') }}" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="{{ $forum->name }}" />
    <meta name="twitter:description" content="{{ $forumDesc }}" />
@endpush

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

    @php $canModerate = auth()->check() && auth()->user()->is_admin; @endphp

    @if($canModerate)
        <form id="vx-thread-mod" method="POST" action="{{ route('admin.threads.bulk-destroy') }}"
              onsubmit="return confirm('Delete the selected threads? This cannot be undone.');">
            @csrf
            @method('DELETE')
    @endif

    <ul class="vx-row-divide">
        @forelse($threads as $thread)
            @php
                $lastRead = $lastReadMap[$thread->id] ?? null;
                if ($lastRead && ! $lastRead instanceof \Carbon\Carbon) {
                    $lastRead = \Carbon\Carbon::parse($lastRead);
                }
                $hasUnread = auth()->check()
                    && $lastRead
                    && $thread->last_post_at
                    && $thread->last_post_at->gt($lastRead);
            @endphp
            <li class="py-5 flex items-start gap-6 group">
                @if($canModerate)
                    <label class="flex items-center pt-1 cursor-pointer" onclick="event.stopPropagation()">
                        <input type="checkbox" name="ids[]" value="{{ $thread->id }}" class="vx-mod-check"
                               style="border-color:var(--border);color:var(--accent)" />
                    </label>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($thread->is_pinned)<span class="vx-chip">Pinned</span>@endif
                        @if($thread->is_locked)<span class="vx-chip" style="color:#991b1b;background:#fee2e2;border-color:#fecaca;">Locked</span>@endif
                        <a href="{{ route('threads.show', [$forum->slug, $thread->slug]) }}"
                           class="vx-display text-lg font-medium vx-heading hover:text-[color:var(--accent)] transition-colors">
                            {{ $thread->title }}
                        </a>
                        @if($hasUnread)
                            <a href="{{ route('threads.unread', [$forum->slug, $thread->slug]) }}"
                               class="inline-flex items-center gap-1 text-[0.68rem] font-mono uppercase tracking-wider px-1.5 py-0.5 rounded"
                               style="background:var(--accent-weak);color:var(--accent);border:1px solid color-mix(in oklch, var(--accent) 30%, transparent)"
                               title="Jump to first unread post">
                                New
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7l7 7-7 7"/></svg>
                            </a>
                        @endif
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

    @if($canModerate)
        </form>

        <div id="vx-mod-bar"
             style="position:fixed;bottom:1.25rem;left:50%;transform:translateX(-50%) translateY(120%);
                    background:var(--surface);border:1px solid var(--border-strong);border-radius:0.75rem;
                    box-shadow:0 10px 30px rgba(0,0,0,0.18);padding:0.65rem 0.85rem 0.65rem 1rem;
                    display:flex;align-items:center;gap:0.75rem;font-size:0.85rem;z-index:50;
                    transition:transform 160ms ease;">
            <span id="vx-mod-count" style="color:var(--text-muted);font-family:'JetBrains Mono',monospace">0 selected</span>
            <button type="button" id="vx-mod-clear" class="vx-btn-secondary" style="padding:0.3rem 0.7rem;font-size:0.75rem">Clear</button>
            <button type="submit" form="vx-thread-mod"
                    style="padding:0.4rem 0.9rem;font-size:0.8rem;border-radius:0.5rem;background:#dc2626;color:#fff;border:1px solid #dc2626;font-weight:500;cursor:pointer">
                Delete selected
            </button>
        </div>

        <script>
            (function () {
                var bar = document.getElementById('vx-mod-bar');
                var count = document.getElementById('vx-mod-count');
                var clear = document.getElementById('vx-mod-clear');
                var boxes = document.querySelectorAll('.vx-mod-check');
                function refresh() {
                    var n = 0;
                    boxes.forEach(function (b) { if (b.checked) n++; });
                    count.textContent = n + (n === 1 ? ' selected' : ' selected');
                    bar.style.transform = 'translateX(-50%) translateY(' + (n ? '0' : '120%') + ')';
                }
                boxes.forEach(function (b) { b.addEventListener('change', refresh); });
                clear.addEventListener('click', function () { boxes.forEach(function (b) { b.checked = false; }); refresh(); });
            })();
        </script>
    @endif

    <div class="mt-6">{{ $threads->links() }}</div>
@endsection
