<nav class="text-sm text-gray-500 mb-4">
    @foreach($items as $i => $item)
        @if(!$loop->first)<span class="mx-2">/</span>@endif
        @if(!empty($item['url']) && !$loop->last)
            <a href="{{ $item['url'] }}" class="hover:underline">{{ $item['label'] }}</a>
        @else
            <span class="text-gray-800">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
