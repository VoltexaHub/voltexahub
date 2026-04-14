@extends('theme::layout')

@section('title', $thread->title.' · '.config('app.name'))

@push('scripts')
    @vite(['resources/js/markdown-editor.js', 'resources/js/thread-live.js', 'resources/js/reactions.js'])
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $forum->name, 'url' => route('forums.show', $forum->slug)],
        ['label' => $thread->title],
    ]])

    <header class="mb-10">
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
    </header>

    <div class="space-y-0 border-t vx-hairline" data-thread-posts data-thread-id="{{ $thread->id }}">
        @foreach($posts as $post)
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
            <section class="mt-10 pt-8 border-t vx-hairline">
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
