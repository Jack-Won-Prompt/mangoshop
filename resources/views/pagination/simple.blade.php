@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="페이지 네비게이션">
        {{-- 이전 --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo;</a>
        @endif

        {{-- 현재 페이지 주변 + 처음/끝만 표시(…으로 생략) --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="dots" aria-disabled="true">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="active"><span>{{ $page }}</span></span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- 다음 --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">&rsaquo;</a>
        @else
            <span aria-disabled="true">&rsaquo;</span>
        @endif
    </nav>
@endif
