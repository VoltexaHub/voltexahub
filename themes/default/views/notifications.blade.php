@extends('theme::layout')

@section('title', 'Notifications · '.config('app.name'))

@section('content')
    <header class="mb-8 flex items-end justify-between pb-5 border-b vx-hairline">
        <div>
            <p class="vx-meta mb-2">Activity</p>
            <h1 class="vx-display text-4xl font-semibold tracking-tight vx-heading">Notifications</h1>
        </div>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="vx-meta hover:text-[color:var(--accent)]">Mark all read</button>
        </form>
    </header>

    <ul class="vx-row-divide">
        @forelse($notifications as $n)
            @php $d = $n->data; @endphp
            <li class="py-4 flex items-start gap-3 {{ $n->read_at ? '' : 'pl-3 border-l-2 -ml-3' }}" style="{{ $n->read_at ? '' : 'border-color:var(--accent);' }}">
                <div class="flex-1 min-w-0">
                    @if(($d['type'] ?? null) === 'thread_reply')
                        <p class="text-sm vx-heading">
                            <span class="vx-display font-medium">{{ $d['author_name'] ?? 'Someone' }}</span>
                            <span class="vx-muted">replied in</span>
                            <a href="{{ route('threads.show', [$d['forum_slug'], $d['thread_slug']]) }}#post-{{ $d['post_id'] }}" class="vx-link">{{ $d['thread_title'] }}</a>
                        </p>
                    @elseif(($d['type'] ?? null) === 'private_message')
                        <p class="text-sm vx-heading">
                            <span class="vx-display font-medium">{{ $d['author_name'] ?? 'Someone' }}</span>
                            <span class="vx-muted">sent you a</span>
                            <a href="{{ route('messages.show', $d['conversation_id']) }}" class="vx-link">message</a>
                        </p>
                    @elseif(($d['type'] ?? null) === 'user_mentioned')
                        <p class="text-sm vx-heading">
                            <span class="vx-display font-medium">{{ $d['mentioner_name'] ?? 'Someone' }}</span>
                            <span class="vx-muted">mentioned you in</span>
                            <a href="{{ route('threads.show', [$d['forum_slug'], $d['thread_slug']]) }}#post-{{ $d['post_id'] }}" class="vx-link">{{ $d['thread_title'] }}</a>
                        </p>
                    @elseif(($d['type'] ?? null) === 'post_reaction')
                        <p class="text-sm vx-heading">
                            <span class="vx-display font-medium">{{ $d['reactor_name'] ?? 'Someone' }}</span>
                            <span class="vx-muted">reacted</span>
                            <span class="text-base align-middle">{{ $d['emoji'] ?? '👍' }}</span>
                            <span class="vx-muted">to your post in</span>
                            <a href="{{ route('threads.show', [$d['forum_slug'], $d['thread_slug']]) }}#post-{{ $d['post_id'] }}" class="vx-link">{{ $d['thread_title'] }}</a>
                        </p>
                    @else
                        <p class="text-sm vx-heading">{{ $d['type'] ?? 'Notification' }}</p>
                    @endif
                    @if(! empty($d['excerpt']))
                        <p class="vx-muted text-sm mt-1 line-clamp-2">{{ $d['excerpt'] }}</p>
                    @endif
                    <p class="vx-meta normal-case tracking-normal text-[0.7rem] mt-1">{{ $n->created_at->diffForHumans() }}</p>
                </div>
                <form method="POST" action="{{ route('notifications.destroy', $n->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="vx-subtle hover:text-red-500 text-lg leading-none" title="Delete">&times;</button>
                </form>
            </li>
        @empty
            <li class="py-16 text-center vx-display text-xl vx-muted italic">No notifications.</li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $notifications->links() }}</div>
@endsection
