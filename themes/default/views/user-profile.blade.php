@extends('theme::layout')

@section('title', $user->name.' · '.config('app.name'))

@section('content')
    @include('theme::partials.breadcrumbs', ['items' => [
        ['label' => 'Forums', 'url' => route('home')],
        ['label' => $user->name],
    ]])

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 flex items-center gap-6">
        <img src="{{ $user->avatar_url }}" alt="" class="w-20 h-20 rounded-full" />
        <div class="flex-1">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $user->name }}</h1>
                @if($user->is_admin)
                    <span class="inline-block px-2 py-0.5 text-xs rounded bg-indigo-100 text-indigo-700">Admin</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">Joined {{ $user->created_at->format('F j, Y') }} · {{ $user->created_at->diffForHumans() }}</p>
            @auth
                @if(auth()->id() !== $user->id)
                    <a href="{{ route('messages.create', ['to' => $user->id]) }}"
                       class="inline-block mt-2 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded hover:bg-indigo-700">
                        Send Message
                    </a>
                @endif
            @endauth
        </div>
        <div class="grid grid-cols-2 gap-4 text-center">
            <div>
                <div class="text-2xl font-semibold text-gray-900">{{ $threadsCount }}</div>
                <div class="text-xs text-gray-500 uppercase">Threads</div>
            </div>
            <div>
                <div class="text-2xl font-semibold text-gray-900">{{ $postsCount }}</div>
                <div class="text-xs text-gray-500 uppercase">Posts</div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <section class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <header class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h2 class="font-semibold text-gray-800">Recent threads</h2>
            </header>
            <ul class="divide-y divide-gray-100">
                @forelse($recentThreads as $thread)
                    <li class="px-4 py-3">
                        <a href="{{ route('threads.show', [$thread->forum->slug, $thread->slug]) }}"
                           class="text-indigo-600 hover:underline font-medium truncate block">{{ $thread->title }}</a>
                        <div class="text-xs text-gray-500">
                            in {{ $thread->forum->name }} · {{ $thread->created_at->diffForHumans() }}
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-500 text-sm">No threads yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <header class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h2 class="font-semibold text-gray-800">Recent posts</h2>
            </header>
            <ul class="divide-y divide-gray-100">
                @forelse($recentPosts as $post)
                    <li class="px-4 py-3">
                        <a href="{{ route('threads.show', [$post->thread->forum->slug, $post->thread->slug]) }}#post-{{ $post->id }}"
                           class="text-indigo-600 hover:underline font-medium truncate block">{{ $post->thread->title }}</a>
                        <div class="text-xs text-gray-500">
                            in {{ $post->thread->forum->name }} · {{ $post->created_at->diffForHumans() }}
                        </div>
                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ Str::limit($post->body, 160) }}</p>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-500 text-sm">No posts yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
