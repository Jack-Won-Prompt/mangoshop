@php
    $pps = collect($site['popups'] ?? [$site['popup'] ?? []]);
    $today = now()->toDateString();
    $active = $pps->filter(fn ($p) => ($p['enabled'] ?? false) && ! empty($p['image'])
        && (empty($p['start']) || $today >= $p['start']) && (empty($p['end']) || $today <= $p['end']))->values();
@endphp
@if($active->count())
<div id="mgPopHost">
    @foreach($active as $i => $pp)
        @php
            $img  = preg_match('#^https?://#i', $pp['image']) ? $pp['image'] : asset(ltrim($pp['image'], '/'));
            $raw  = trim((string) ($pp['link'] ?? ''));
            $link = $raw === '' ? route('community.inquiry') : (preg_match('#^https?://#i', $raw) ? $raw : url('/'.ltrim($raw, '/')));
            $key  = 'mgpop_'.substr(md5($img.'|'.$link), 0, 10);
            $tgt  = ($pp['new_window'] ?? false) ? 'target="_blank" rel="noopener"' : '';
            // 겹치지 않게 분리 배치: 0=좌상단, 1=좌하단, 그 외 스태거
            $pos  = $i === 0 ? 'left:20px;top:90px'
                  : ($i === 1 ? 'left:20px;bottom:24px'
                  : 'left:'.(20 + $i * 30).'px;top:'.(90 + $i * 30).'px');
        @endphp
        <div class="mgpop-box" data-key="{{ $key }}" style="{{ $pos }}" hidden>
            <div class="mgpop-bar" data-drag>
                <span class="mgpop-title">{{ $pp['title'] ?: '망고샵 이벤트' }}</span>
                <button type="button" class="mgpop-x" data-close aria-label="닫기">✕</button>
            </div>
            <a class="mgpop-img" href="{{ $link }}" {!! $tgt !!}><img src="{{ $img }}" alt="{{ $pp['title'] ?: '망고샵 이벤트' }}"></a>
            @if(!empty($pp['button']))
                <a class="mgpop-cta" href="{{ $link }}" {!! $tgt !!}>{{ $pp['button'] }} <span>›</span></a>
            @endif
            <div class="mgpop-foot"><label class="mgpop-hide"><input type="checkbox" data-hide> 오늘 하루 보지 않기</label></div>
        </div>
    @endforeach
</div>

<style>
    #mgPopHost .mgpop-box{position:fixed;z-index:2000;width:320px;max-width:calc(100% - 24px);background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 16px 44px rgba(0,0,0,.28);border:1px solid #ececE5}
    #mgPopHost .mgpop-bar{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 12px;background:#123b26;color:#fff;cursor:move;user-select:none;touch-action:none}
    #mgPopHost .mgpop-title{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    #mgPopHost .mgpop-x{border:0;background:none;color:#fff;font-size:15px;line-height:1;cursor:pointer;padding:2px 4px}
    #mgPopHost .mgpop-img{display:block}
    #mgPopHost .mgpop-img img{width:100%;height:auto;display:block}
    #mgPopHost .mgpop-cta{display:block;padding:14px;text-align:center;background:#ffb400;color:#3a2a00;font-weight:800;font-size:15px;text-decoration:none}
    #mgPopHost .mgpop-cta span{margin-left:3px}
    #mgPopHost .mgpop-foot{padding:9px 14px;background:#f6f6f2;font-size:12.5px;color:#666}
    #mgPopHost .mgpop-hide{display:inline-flex;align-items:center;gap:6px;cursor:pointer}
    @media(max-width:600px){#mgPopHost .mgpop-box{width:min(320px,calc(100% - 20px))}}
</style>
<script>
(function(){
    var today=new Date().toDateString();
    document.querySelectorAll('#mgPopHost .mgpop-box').forEach(function(box){
        var key=box.dataset.key;
        try{ if(localStorage.getItem(key)===today){ box.remove(); return; } }catch(e){}
        box.hidden=false;

        box.querySelector('[data-close]').addEventListener('click',function(){
            try{ var c=box.querySelector('[data-hide]'); if(c&&c.checked) localStorage.setItem(key,today); }catch(e){}
            box.remove();
        });

        // 드래그 이동 (마우스/터치)
        var bar=box.querySelector('[data-drag]'), drag=false, sx=0, sy=0, ox=0, oy=0;
        function down(e){
            drag=true;
            var p=e.touches?e.touches[0]:e; sx=p.clientX; sy=p.clientY;
            var r=box.getBoundingClientRect(); ox=r.left; oy=r.top;
            box.style.left=ox+'px'; box.style.top=oy+'px'; box.style.transform='none';
            if(e.cancelable) e.preventDefault();
        }
        function move(e){
            if(!drag) return;
            var p=e.touches?e.touches[0]:e;
            var nx=Math.max(4, Math.min(window.innerWidth-40, ox+p.clientX-sx));
            var ny=Math.max(4, Math.min(window.innerHeight-40, oy+p.clientY-sy));
            box.style.left=nx+'px'; box.style.top=ny+'px';
            if(e.cancelable) e.preventDefault();
        }
        function up(){ drag=false; }
        bar.addEventListener('mousedown',down); bar.addEventListener('touchstart',down,{passive:false});
        document.addEventListener('mousemove',move); document.addEventListener('touchmove',move,{passive:false});
        document.addEventListener('mouseup',up); document.addEventListener('touchend',up);
    });
})();
</script>
@endif
