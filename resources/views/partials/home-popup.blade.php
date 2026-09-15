@php($pp = $site['popup'] ?? [])
@php($today = now()->toDateString())
@php($inWindow = (empty($pp['start']) || $today >= $pp['start']) && (empty($pp['end']) || $today <= $pp['end']))
@if(($pp['enabled'] ?? false) && ! empty($pp['image']) && $inWindow)
    @php($img = preg_match('#^https?://#i', $pp['image']) ? $pp['image'] : asset(ltrim($pp['image'], '/')))
    @php($rawLink = trim((string) ($pp['link'] ?? '')))
    @php($link = $rawLink === '' ? route('community.inquiry') : (preg_match('#^https?://#i', $rawLink) ? $rawLink : url('/'.ltrim($rawLink, '/'))))
    @php($popKey = 'mgpop_'.substr(md5($img.'|'.$link), 0, 10))

    <div id="mgPopup" class="mgpop" hidden aria-hidden="true">
        <div class="mgpop-box" role="dialog" aria-label="{{ $pp['title'] ?: '이벤트 안내' }}">
            <a class="mgpop-img" href="{{ $link }}" @if($pp['new_window'] ?? false)target="_blank" rel="noopener"@endif>
                <img src="{{ $img }}" alt="{{ $pp['title'] ?: '망고샵 이벤트' }}">
            </a>
            @if(!empty($pp['button']))
                <a class="mgpop-cta" href="{{ $link }}" @if($pp['new_window'] ?? false)target="_blank" rel="noopener"@endif>{{ $pp['button'] }} <span>›</span></a>
            @endif
            <div class="mgpop-foot">
                <label class="mgpop-hide"><input type="checkbox" id="mgPopHideChk"> 오늘 하루 보지 않기</label>
                <button type="button" class="mgpop-close" onclick="mgPopClose()">닫기 ✕</button>
            </div>
        </div>
    </div>

    <style>
        /* 메인 화면을 가리지 않는 코너 플로팅 팝업(딤 오버레이 없음) */
        .mgpop{position:fixed;z-index:2000;top:86px;left:24px;width:340px;max-width:calc(100% - 32px)}
        .mgpop-box{position:relative;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 16px 44px rgba(0,0,0,.28);border:1px solid #ececE5}
        .mgpop-img{display:block}
        .mgpop-img img{width:100%;height:auto;display:block}
        .mgpop-cta{display:block;margin:0;padding:15px;text-align:center;background:#123b26;color:#fff;font-weight:800;font-size:16px;text-decoration:none}
        .mgpop-cta span{margin-left:4px}
        .mgpop-foot{display:flex;justify-content:space-between;align-items:center;padding:11px 16px;background:#f6f6f2;font-size:13px;color:#555}
        .mgpop-hide{display:inline-flex;align-items:center;gap:6px;cursor:pointer}
        .mgpop-close{border:0;background:none;color:#555;cursor:pointer;font-size:13px;font-weight:600}
        @media(max-width:600px){.mgpop{left:50%;transform:translateX(-50%);top:64px;width:min(340px,calc(100% - 24px))}}
    </style>
    <script>
    (function(){
        var key='{{ $popKey }}';
        var today=new Date().toDateString();
        try{ if(localStorage.getItem(key)===today) return; }catch(e){}
        var el=document.getElementById('mgPopup'); if(!el)return;
        el.hidden=false;
        window.mgPopClose=function(){
            try{ var c=document.getElementById('mgPopHideChk'); if(c&&c.checked) localStorage.setItem(key,today); }catch(e){}
            el.hidden=true;
        };
        document.addEventListener('keydown',function(e){ if(e.key==='Escape') window.mgPopClose(); });
    })();
    </script>
@endif
