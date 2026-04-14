@php
    $summary = $post->reactionSummary(auth()->id());
    $allEmoji = \App\Models\Reaction::ALLOWED;
    $used = collect($summary)->pluck('emoji')->all();
    $available = array_values(array_diff($allEmoji, $used));
@endphp

<div class="mt-4 flex items-center flex-wrap gap-1.5 vx-reactions" data-post-id="{{ $post->id }}">
    @foreach($summary as $row)
        <form method="POST" action="{{ route('posts.reactions.toggle', $post->id) }}" class="vx-react-form">
            @csrf
            <input type="hidden" name="emoji" value="{{ $row['emoji'] }}" />
            <button type="submit"
                    @guest disabled title="Log in to react" @endguest
                    data-emoji="{{ $row['emoji'] }}"
                    data-mine="{{ $row['mine'] ? '1' : '0' }}"
                    class="vx-react-pill inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-mono tabular-nums border transition-colors
                           {{ $row['mine']
                                ? 'border-[color:var(--accent)] bg-[color:var(--accent-weak)] text-[color:var(--accent)]'
                                : 'vx-hairline vx-muted hover:border-[color:var(--accent)] hover:text-[color:var(--accent)]' }}">
                <span class="text-[0.95rem] leading-none">{{ $row['emoji'] }}</span>
                <span class="vx-react-count">{{ $row['count'] }}</span>
            </button>
        </form>
    @endforeach

    @auth
        @if(count($available) > 0)
            <details class="relative vx-react-picker">
                <summary class="list-none cursor-pointer inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs border vx-hairline vx-subtle hover:border-[color:var(--accent)] hover:text-[color:var(--accent)] transition-colors">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/></svg>
                    React
                </summary>
                <div class="absolute z-10 mt-2 vx-card p-1.5 flex gap-1 shadow-sm">
                    @foreach($available as $e)
                        <form method="POST" action="{{ route('posts.reactions.toggle', $post->id) }}" class="vx-react-form">
                            @csrf
                            <input type="hidden" name="emoji" value="{{ $e }}" />
                            <button type="submit" class="vx-react-add w-8 h-8 rounded-full hover:bg-[color:var(--surface-mute)] text-lg leading-none transition-colors" data-emoji="{{ $e }}" title="React with {{ $e }}">{{ $e }}</button>
                        </form>
                    @endforeach
                </div>
            </details>
        @endif
    @endauth
</div>
