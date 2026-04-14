@extends('theme::layout')

@section('title', $thread->title.' · '.config('app.name'))

@push('scripts')
    @vite(['resources/js/markdown-editor.js', 'resources/js/thread-live.js'])
@endpush

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $forum->name, 'url' => route('forums.show', $forum->slug)],
        ['label' => $thread->title],
    ]])

    <h1 class="text-2xl font-semibold vx-heading mb-5">{{ $thread->title }}</h1>

    <div class="space-y-4" data-thread-posts data-thread-id="{{ $thread->id }}">
        @foreach($posts as $post)
            <article class="vx-card overflow-hidden" id="post-{{ $post->id }}">
                <header class="vx-card-header flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2.5">
                        @if($post->author)
                            <a href="{{ route('users.show', $post->author) }}" class="flex items-center gap-2.5 hover:opacity-90">
                                <img src="{{ $post->author->avatar_url }}" alt="" class="w-8 h-8 rounded-full ring-1 ring-slate-200 dark:ring-slate-700" />
                                <span class="font-medium vx-heading hover:text-indigo-500">{{ $post->author->name }}</span>
                            </a>
                        @else
                            <span class="vx-muted">[deleted]</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="vx-subtle tabular-nums">{{ $post->created_at->format('M j, Y g:i A') }}</span>
                        @auth
                            @if(auth()->user()->is_admin || auth()->id() === $post->user_id)
                                <a href="{{ route('posts.edit', $post->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">Edit</a>
                                <form method="POST" action="{{ route('posts.destroy', $post->id) }}" class="inline" onsubmit="return confirm('Delete this post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-xs">Delete</button>
                                </form>
                            @elseif(auth()->id() !== $post->user_id)
                                <details class="relative">
                                    <summary class="vx-muted hover:text-red-500 text-xs cursor-pointer list-none">Report</summary>
                                    <form method="POST" action="{{ route('posts.report', $post->id) }}"
                                          class="absolute right-0 mt-2 w-64 vx-card p-3 z-10 space-y-2">
                                        @csrf
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">Reason</label>
                                        <select name="reason" class="vx-input text-sm" required>
                                            <option value="spam">Spam</option>
                                            <option value="harassment">Harassment</option>
                                            <option value="off-topic">Off-topic</option>
                                            <option value="illegal">Illegal content</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <textarea name="note" rows="2" maxlength="500" placeholder="Optional note..." class="vx-input text-sm"></textarea>
                                        <button type="submit" class="w-full vx-btn bg-red-600 text-white hover:bg-red-500">Submit report</button>
                                    </form>
                                </details>
                            @endif
                        @endauth
                    </div>
                </header>
                <div class="px-5 py-5 prose prose-sm max-w-none prose-indigo dark:prose-invert">
                    {!! $post->body_html !!}
                </div>
                @if($post->edited_at)
                    <div class="px-5 pb-3 text-xs vx-subtle italic">edited {{ $post->edited_at->diffForHumans() }}</div>
                @endif
            </article>
        @endforeach
    </div>

    <div class="mt-5">{{ $posts->links() }}</div>

    @auth
        @if(!$thread->is_locked)
            <section class="mt-6 vx-card p-5">
                <h2 class="text-lg font-medium vx-heading mb-3">Reply</h2>
                <form method="POST" action="{{ route('posts.store', [$forum->slug, $thread->slug]) }}">
                    @csrf
                    <textarea name="body" rows="5" data-markdown class="vx-input" placeholder="Write your reply in markdown...">{{ old('body') }}</textarea>
                    @error('body')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="vx-btn-primary">Post Reply</button>
                    </div>
                </form>
            </section>
        @else
            <div class="mt-6 text-center text-sm text-red-600 dark:text-red-400">This thread is locked.</div>
        @endif
    @else
        <div class="mt-6 text-center text-sm vx-muted">
            <a href="{{ route('login') }}" class="vx-link">Log in</a> to reply.
        </div>
    @endauth
@endsection
