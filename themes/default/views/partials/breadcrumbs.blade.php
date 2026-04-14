<nav class="text-sm vx-muted mb-5">
    @foreach($items as $i => $item)
        @if(!$loop->first)<span class="mx-2 vx-subtle">/</span>@endif
        @if(!empty($item['url']) && !$loop->last)
            <a href="{{ $item['url'] }}" class="hover:vx-heading">{{ $item['label'] }}</a>
        @else
            <span class="vx-heading">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
