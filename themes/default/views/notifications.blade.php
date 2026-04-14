@extends('theme::layout')

@section('title', 'Notifications · '.config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold text-gray-900">Notifications</h1>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="text-sm text-indigo-600 hover:underline">Mark all read</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <ul class="divide-y divide-gray-100">
            @forelse($notifications as $n)
                @php $d = $n->data; @endphp
                <li class="px-4 py-3 {{ $n->read_at ? '' : 'bg-indigo-50/40' }}">
                    <div class="flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            @if(($d['type'] ?? null) === 'thread_reply')
                                <p class="text-sm text-gray-900">
                                    <strong>{{ $d['author_name'] ?? 'Someone' }}</strong> replied in
                                    <a href="{{ route('threads.show', [$d['forum_slug'], $d['thread_slug']]) }}#post-{{ $d['post_id'] }}"
                                       class="text-indigo-600 hover:underline">{{ $d['thread_title'] }}</a>
                                </p>
                            @elseif(($d['type'] ?? null) === 'private_message')
                                <p class="text-sm text-gray-900">
                                    <strong>{{ $d['author_name'] ?? 'Someone' }}</strong> sent you a
                                    <a href="{{ route('messages.show', $d['conversation_id']) }}"
                                       class="text-indigo-600 hover:underline">message</a>
                                </p>
                            @else
                                <p class="text-sm text-gray-900">{{ $d['type'] ?? 'Notification' }}</p>
                            @endif
                            @if(! empty($d['excerpt']))
                                <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $d['excerpt'] }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                        </div>
                        <form method="POST" action="{{ route('notifications.destroy', $n->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-gray-400 hover:text-red-600" title="Delete">&times;</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-gray-500">No notifications yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
