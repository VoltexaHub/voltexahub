@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4 pt-6 border-t" style="border-color:var(--border)">
        <div class="text-xs font-mono uppercase tracking-wider" style="color:var(--text-subtle)">
            @if ($paginator->total() > 0)
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
            @endif
        </div>

        <ul class="flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li><span class="px-3 py-1.5 text-sm font-mono rounded-md opacity-30 cursor-not-allowed" style="color:var(--text-muted)" aria-disabled="true">←</span></li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                       class="px-3 py-1.5 text-sm font-mono rounded-md border hover:border-[color:var(--accent)] hover:text-[color:var(--accent)] transition-colors"
                       style="color:var(--text-muted);border-color:var(--border)">←</a>
                </li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 vx-subtle">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="px-3 py-1.5 text-sm font-mono tabular-nums rounded-md text-white"
                                      style="background:var(--accent);border:1px solid var(--accent)">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}"
                                   class="px-3 py-1.5 text-sm font-mono tabular-nums rounded-md border hover:border-[color:var(--accent)] hover:text-[color:var(--accent)] transition-colors"
                                   style="color:var(--text-muted);border-color:var(--border)">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                       class="px-3 py-1.5 text-sm font-mono rounded-md border hover:border-[color:var(--accent)] hover:text-[color:var(--accent)] transition-colors"
                       style="color:var(--text-muted);border-color:var(--border)">→</a>
                </li>
            @else
                <li><span class="px-3 py-1.5 text-sm font-mono rounded-md opacity-30 cursor-not-allowed" style="color:var(--text-muted)" aria-disabled="true">→</span></li>
            @endif
        </ul>
    </nav>
@endif
