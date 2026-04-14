@extends('theme::layout')

@section('title', $thread->title.' · '.config('app.name'))

@push('head')
    <meta property="og:title" content="{{ $thread->title }}" />
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($posts->first()?->body ?? ''), 200) }}" />
    <meta property="og:image" content="{{ route('og.thread', $thread) }}" />
    <meta property="og:type" content="article" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="{{ route('og.thread', $thread) }}" />
@endpush

@push('scripts')
    @vite(['resources/js/markdown-editor.js', 'resources/js/thread-live.js', 'resources/js/reactions.js', 'resources/js/quote.js', 'resources/js/mentions.js'])
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $forum->name, 'url' => route('forums.show', $forum->slug)],
        ['label' => $thread->title],
    ]])

    <header class="mb-10">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-3">
                    @if($thread->is_pinned)<span class="vx-chip">Pinned</span>@endif
                    @if($thread->is_locked)<span class="vx-chip" style="color:#991b1b;background:#fee2e2;border-color:#fecaca;">Locked</span>@endif
                </div>
                <h1 class="vx-display text-[2.25rem] md:text-[2.75rem] leading-[1.1] font-semibold tracking-tight vx-heading">
                    {{ $thread->title }}
                </h1>
                <p class="vx-meta mt-4 normal-case tracking-normal text-[0.78rem]">
                    Started by
                    @if($thread->author)<a href="{{ route('users.show', $thread->author) }}" class="vx-heading hover:text-[color:var(--accent)] font-medium">{{ $thread->author->name }}</a>@else [deleted]@endif
                    <span class="mx-2 text-[color:var(--accent)]">·</span>
                    {{ $thread->created_at->format('F j, Y') }}
                    <span class="mx-2 text-[color:var(--accent)]">·</span>
                    <span class="vx-meta">{{ $thread->posts_count }} replies · {{ $thread->views_count }} views</span>
                </p>
            </div>
            @auth
                <div class="shrink-0 flex items-center gap-2">
                    @if(auth()->user()->is_admin || auth()->id() === $thread->user_id)
                        <a href="{{ route('threads.export', [$forum->slug, $thread->slug]) }}"
                           class="vx-btn-secondary text-xs py-1.5 px-3" title="Download thread as markdown">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Export
                        </a>
                    @endif
                    @if($bookmarked ?? false)
                        <form method="POST" action="{{ route('bookmarks.destroy', $thread) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="vx-btn-secondary text-xs py-1.5 px-3" title="Remove bookmark" style="color:var(--accent);border-color:var(--accent)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                Saved
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('bookmarks.store', $thread) }}">
                            @csrf
                            <button type="submit" class="vx-btn-secondary text-xs py-1.5 px-3" title="Bookmark this thread">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                Save
                            </button>
                        </form>
                    @endif

                    @if($mutedByUser ?? false)
                        <form method="POST" action="{{ route('threads.unmute', $thread) }}">
                            @csrf
                            <button type="submit" class="vx-btn-secondary text-xs py-1.5 px-3" title="You won't be notified of new replies">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.73 21a2 2 0 01-3.46 0M18.63 13A17.89 17.89 0 0118 8M6.26 6.26A5.86 5.86 0 006 8c0 7-3 9-3 9h14M18 8a6 6 0 00-9.33-5M1 1l22 22"/></svg>
                                Muted
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('threads.mute', $thread) }}">
                            @csrf
                            <button type="submit" class="vx-btn-secondary text-xs py-1.5 px-3" title="Stop getting notifications for this thread">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14V11a6 6 0 10-12 0v3a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                                Mute
                            </button>
                        </form>
                    @endif
                </div>
            @endauth
        </div>
    </header>

    @if($thread->poll)
        @include('theme::partials.poll', ['poll' => $thread->poll])
    @endif

    @php $markerShown = false; @endphp
    <div class="space-y-0 border-t vx-hairline" data-thread-posts data-thread-id="{{ $thread->id }}">
        @foreach($posts as $post)
            @if(($previouslyReadAt ?? null) && ! $markerShown && $post->created_at->gt($previouslyReadAt) && $loop->index > 0)
                @php $markerShown = true; @endphp
                <div class="vx-unread-marker flex items-center gap-3 py-3 text-[color:var(--accent)]">
                    <span class="flex-1 h-px" style="background:var(--accent);opacity:0.45"></span>
                    <span class="vx-meta normal-case tracking-[0.12em]" style="color:var(--accent)">New since {{ $previouslyReadAt->diffForHumans() }}</span>
                    <span class="flex-1 h-px" style="background:var(--accent);opacity:0.45"></span>
                </div>
            @endif
            <article class="border-b vx-hairline py-7" id="post-{{ $post->id }}">
                <header class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex items-center gap-3">
                        @if($post->author)
                            <a href="{{ route('users.show', $post->author) }}" class="shrink-0">
                                <img src="{{ $post->author->avatar_url }}" alt="" class="w-10 h-10 rounded-full border vx-hairline" />
                            </a>
                            <div>
                                <a href="{{ route('users.show', $post->author) }}" class="vx-display text-[0.95rem] font-medium vx-heading hover:text-[color:var(--accent)]">{{ $post->author->name }}</a>
                                <p class="vx-meta normal-case tracking-normal text-[0.7rem] mt-0.5">
                                    <span>#{{ ($posts->firstItem() ?? 1) + $loop->index }}</span>
                                    <span class="opacity-60 mx-1">·</span>
                                    <span>{{ $post->created_at->format('M j, Y · g:i A') }}</span>
                                </p>
                            </div>
                        @else
                            <div>
                                <p class="vx-muted italic">[deleted]</p>
                                <p class="vx-meta">{{ $post->created_at->format('M j, Y') }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 text-[0.72rem] vx-subtle">
                        @auth
                            @if(! $thread->is_locked)
                                <button type="button" class="hover:text-[color:var(--accent)]"
                                        data-quote
                                        data-quote-author="{{ $post->author?->name }}"
                                        data-quote-body="{{ $post->body }}">Quote</button>
                            @endif
                            @if(auth()->user()->is_admin || auth()->id() === $post->user_id)
                                <a href="{{ route('posts.edit', $post->id) }}" class="hover:text-[color:var(--accent)]">Edit</a>
                                <form method="POST" action="{{ route('posts.destroy', $post->id) }}" class="inline" onsubmit="return confirm('Delete this post?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="hover:text-red-500">Delete</button>
                                </form>
                            @elseif(auth()->id() !== $post->user_id)
                                <details class="relative">
                                    <summary class="cursor-pointer list-none hover:text-red-500">Report</summary>
                                    <form method="POST" action="{{ route('posts.report', $post->id) }}"
                                          class="absolute right-0 mt-2 w-64 vx-card p-3 z-10 space-y-2 shadow-sm">
                                        @csrf
                                        <label class="block vx-meta">Reason</label>
                                        <select name="reason" class="vx-input text-sm" required>
                                            <option value="spam">Spam</option>
                                            <option value="harassment">Harassment</option>
                                            <option value="off-topic">Off-topic</option>
                                            <option value="illegal">Illegal content</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <textarea name="note" rows="2" maxlength="500" placeholder="Optional note…" class="vx-input text-sm"></textarea>
                                        <button type="submit" class="w-full vx-btn" style="background:#dc2626;color:#fff;">Submit</button>
                                    </form>
                                </details>
                            @endif
                        @endauth
                    </div>
                </header>
                <div class="pl-[3.25rem] vx-prose">
                    {!! $post->body_html !!}
                    @if($post->author?->signature_html)
                        <div class="mt-5 pt-3 border-t vx-hairline text-[0.85rem]" style="color:var(--text-muted)">
                            {!! $post->author->signature_html !!}
                        </div>
                    @endif
                </div>
                <div class="pl-[3.25rem]">
                    @include('theme::partials.reactions', ['post' => $post])
                </div>
                @if($post->edited_at)
                    <div class="pl-[3.25rem] mt-3 vx-meta normal-case tracking-normal text-[0.7rem] italic">edited {{ $post->edited_at->diffForHumans() }}</div>
                @endif
            </article>
        @endforeach
    </div>

    <div class="mt-6">{{ $posts->links() }}</div>

    @auth
        @if(!$thread->is_locked)
            <section class="mt-10 pt-8 border-t vx-hairline" data-reply-form>
                <p class="vx-meta mb-3 text-[color:var(--accent)]">Add to the conversation</p>
                <form method="POST" action="{{ route('posts.store', [$forum->slug, $thread->slug]) }}">
                    @csrf
                    <textarea name="body" rows="5" data-markdown class="vx-input" placeholder="Write your reply in markdown…">{{ old('body') }}</textarea>
                    @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="vx-btn-primary">Post Reply</button>
                    </div>
                </form>
            </section>
        @else
            <p class="mt-10 pt-8 border-t vx-hairline text-center vx-meta">· thread locked ·</p>
        @endif
    @else
        <p class="mt-10 pt-8 border-t vx-hairline text-center vx-muted text-sm">
            <a href="{{ route('login') }}" class="vx-link">Log in</a> to reply.
        </p>
    @endauth
@endsection
