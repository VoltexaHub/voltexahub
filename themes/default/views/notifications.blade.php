@extends('theme::layout')

@section('title', 'Notifications · '.config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-semibold vx-heading">Notifications</h1>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="text-sm vx-link">Mark all read</button>
        </form>
    </div>

    <div class="vx-card overflow-hidden">
        <ul class="vx-row-divide">
            @forelse($notifications as $n)
                @php $d = $n->data; @endphp
                <li class="px-4 py-3 {{ $n->read_at ? '' : 'bg-indigo-50/40 dark:bg-indigo-950/20' }}">
                    <div class="flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            @if(($d['type'] ?? null) === 'thread_reply')
                                <p class="text-sm vx-heading">
                                    <strong>{{ $d['author_name'] ?? 'Someone' }}</strong> replied in
                                    <a href="{{ route('threads.show', [$d['forum_slug'], $d['thread_slug']]) }}#post-{{ $d['post_id'] }}" class="vx-link">{{ $d['thread_title'] }}</a>
                                </p>
                            @elseif(($d['type'] ?? null) === 'private_message')
                                <p class="text-sm vx-heading">
                                    <strong>{{ $d['author_name'] ?? 'Someone' }}</strong> sent you a
                                    <a href="{{ route('messages.show', $d['conversation_id']) }}" class="vx-link">message</a>
                                </p>
                            @else
                                <p class="text-sm vx-heading">{{ $d['type'] ?? 'Notification' }}</p>
                            @endif
                            @if(! empty($d['excerpt']))
                                <p class="text-sm vx-muted mt-1 line-clamp-2">{{ $d['excerpt'] }}</p>
                            @endif
                            <p class="text-xs vx-subtle mt-1">{{ $n->created_at->diffForHumans() }}</p>
                        </div>
                        <form method="POST" action="{{ route('notifications.destroy', $n->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs vx-subtle hover:text-red-500" title="Delete">&times;</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="px-4 py-10 text-center vx-muted">No notifications yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
