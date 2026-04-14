<nav class="vx-meta mb-6 flex items-center flex-wrap gap-1.5">
    @foreach($items as $i => $item)
        @if(!$loop->first)<span class="text-[color:var(--accent)] opacity-70">/</span>@endif
        @if(!empty($item['url']) && !$loop->last)
            <a href="{{ $item['url'] }}" class="hover:text-[color:var(--accent)] transition-colors">{{ $item['label'] }}</a>
        @else
            <span class="vx-heading normal-case tracking-normal text-sm font-medium">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
