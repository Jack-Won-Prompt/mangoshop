{{-- 메인페이지 상품 홍보 팝업 — 관리자 사이트설정 > 메인 팝업(config('site.popup'))에서 편집 --}}
@php
    $pp    = config('site.popup', []);
    $today = now()->toDateString();
    $ppOn  = ! empty($pp['enabled']) && ! empty($pp['image'])
        && (empty($pp['start']) || $pp['start'] <= $today)
        && (empty($pp['end']) || $pp['end'] >= $today);
@endphp
@if($ppOn)
@php
    $ppAbs  = fn ($v) => (bool) preg_match('#^https?://#i', (string) $v);
    $ppImg  = $ppAbs($pp['image']) ? $pp['image'] : asset(ltrim($pp['image'], '/'));
    $ppLink = empty($pp['link']) ? null : ($ppAbs($pp['link']) ? $pp['link'] : url(ltrim($pp['link'], '/')));
    // 내용이 바뀌면 키가 달라져 '오늘 하루 보지 않기'가 초기화됨
    $ppVer  = substr(md5(json_encode([$pp['image'], $pp['link'] ?? '', $pp['title'] ?? '', $pp['sub'] ?? '', $pp['start'] ?? '', $pp['end'] ?? ''])), 0, 10);
    $ppName = ($pp['title'] ?? '') !== '' ? $pp['title'] : '이벤트 안내';
@endphp
<div class="mg-hpop" id="mgPop" data-ver="{{ $ppVer }}" role="dialog" aria-modal="true" aria-label="{{ $ppName }}" hidden>
    <div class="mg-hpop-dim" data-pop-close></div>
    <div class="mg-hpop-box">
        <button type="button" class="mg-hpop-x" data-pop-close aria-label="팝업 닫기">&times;</button>
        <a class="mg-hpop-link" @if($ppLink) href="{{ $ppLink }}" @if(! empty($pp['new_window'])) target="_blank" rel="noopener" @endif @endif>
            <img src="{{ $ppImg }}" alt="{{ trim(($pp['title'] ?? '').' '.($pp['sub'] ?? '')) }}" class="mg-hpop-img">
            @if(! empty($pp['title']) || ! empty($pp['sub']) || $ppLink)
                <div class="mg-hpop-cap">
                    @if(! empty($pp['title']))<strong>{{ $pp['title'] }}</strong>@endif
                    @if(! empty($pp['sub']))<span class="mg-hpop-sub">{{ $pp['sub'] }}</span>@endif
                    @if($ppLink)<span class="mg-hpop-btn">{{ ($pp['button'] ?? '') !== '' ? $pp['button'] : '자세히 보기' }} &rarr;</span>@endif
                </div>
            @endif
        </a>
        <div class="mg-hpop-foot">
            <button type="button" data-pop-today>오늘 하루 보지 않기</button>
            <button type="button" data-pop-close>닫기</button>
        </div>
    </div>
</div>

@push('head')
<style>
.mg-hpop{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:16px}
.mg-hpop[hidden]{display:none}
.mg-hpop-dim{position:absolute;inset:0;background:rgba(0,0,0,.5)}
.mg-hpop-box{position:relative;width:100%;max-width:420px;max-height:calc(100vh - 32px);overflow:auto;background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.28);animation:mgPopIn .25s ease}
.mg-hpop-link{display:block;color:inherit;text-decoration:none}
.mg-hpop-link[href]{cursor:pointer}
.mg-hpop-img{display:block;width:100%;height:auto;background:var(--mg-soft,#f6f7f9)}
.mg-hpop-cap{padding:16px 18px 18px;text-align:center}
.mg-hpop-cap strong{display:block;font-size:19px;font-weight:800;color:var(--mg-ink,#222);letter-spacing:-.3px}
.mg-hpop-sub{display:block;margin-top:4px;font-size:14px;color:var(--mg-sub,#555)}
.mg-hpop-btn{display:inline-block;margin-top:12px;padding:10px 24px;border-radius:999px;background:var(--mg-primary,#ff6b00);color:#fff;font-weight:700;font-size:14px}
.mg-hpop-link[href]:hover .mg-hpop-btn{background:var(--mg-primary-d,#e85d00)}
.mg-hpop-x{position:absolute;top:10px;right:10px;z-index:1;width:34px;height:34px;border:0;border-radius:50%;background:rgba(0,0,0,.45);color:#fff;font-size:22px;line-height:1;cursor:pointer}
.mg-hpop-foot{display:flex;border-top:1px solid var(--mg-line,#ececef)}
.mg-hpop-foot button{flex:1;padding:13px 0;border:0;background:#fafafa;font-size:13px;color:var(--mg-sub,#555);cursor:pointer}
.mg-hpop-foot button+button{border-left:1px solid var(--mg-line,#ececef)}
.mg-hpop-foot button:hover{background:#f1f1f3;color:var(--mg-ink,#222)}
@keyframes mgPopIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){.mg-hpop-box{animation:none}}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var pop = document.getElementById('mgPop');
    if (!pop) return;
    var key = 'mgPopHide:' + pop.dataset.ver;
    function today() {
        var d = new Date();
        return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
    }
    try { if (localStorage.getItem(key) === today()) return; } catch (e) {}

    function onKey(e) { if (e.key === 'Escape') close(); }
    function close() {
        pop.hidden = true;
        document.removeEventListener('keydown', onKey);
    }
    pop.querySelectorAll('[data-pop-close]').forEach(function (b) { b.addEventListener('click', close); });
    pop.querySelector('[data-pop-today]').addEventListener('click', function () {
        try { localStorage.setItem(key, today()); } catch (e) {}
        close();
    });
    document.addEventListener('keydown', onKey);
    pop.hidden = false;
    pop.querySelector('.mg-hpop-x').focus({ preventScroll: true });
})();
</script>
@endpush
@endif
