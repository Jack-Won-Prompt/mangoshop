@if ($paginator->hasPages())
<nav class="mgpg" role="navigation" aria-label="페이지 이동">
    @if ($paginator->onFirstPage())
        <span class="mgpg-btn off" aria-hidden="true">‹</span>
    @else
        <a class="mgpg-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="이전">‹</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="mgpg-btn dots">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="mgpg-btn on" aria-current="page">{{ $page }}</span>
                @else
                    <a class="mgpg-btn" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a class="mgpg-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="다음">›</a>
    @else
        <span class="mgpg-btn off" aria-hidden="true">›</span>
    @endif
</nav>
<style>
.mgpg{display:flex;justify-content:center;align-items:center;gap:5px;flex-wrap:wrap;margin:22px 0;font-size:13px}
.mgpg .mgpg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 11px;border:1px solid #e2e1da;border-radius:8px;background:#fff;color:#555;text-decoration:none;transition:.12s}
.mgpg a.mgpg-btn:hover{border-color:#123b26;color:#123b26}
.mgpg .mgpg-btn.on{background:#123b26;border-color:#123b26;color:#fff;font-weight:700}
.mgpg .mgpg-btn.off{color:#ccc;background:#f6f6f2;cursor:default}
.mgpg .mgpg-btn.dots{border:0;background:none;color:#aaa;min-width:20px;padding:0}
</style>
@endif
