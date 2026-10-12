@extends('layouts.admin')
@section('title', 'SR 관리')
@section('heading', 'SR 관리')

@section('content')
<div class="sr" id="sr"
     data-store="{{ route('admin.sr.store') }}"
     data-model="{{ $model }}">

    {{-- 좌: 요청 목록 --}}
    <aside class="sr-list">
        <form method="POST" action="{{ route('admin.sr.store') }}">@csrf
            <button class="sr-new" type="submit"><x-icon name="plus" :size="16"/> 새 요청</button>
        </form>
        <div class="sr-threads">
            @forelse($threads as $t)
                <a href="{{ route('admin.sr.show', $t) }}" class="sr-thread {{ $thread && $thread->id === $t->id ? 'on' : '' }}">
                    <span class="t">@if($t->apply_status === 'requested')<i class="sr-dot" title="반영 요청됨"></i>@elseif($t->apply_status === 'done')<i class="sr-dot done" title="반영 완료"></i>@endif{{ $t->title }}</span>
                    <span class="d">{{ optional($t->last_message_at)->format('m/d H:i') }}</span>
                </a>
            @empty
                <div class="sr-empty-list">아직 요청이 없습니다.<br>‘새 요청’으로 시작하세요.</div>
            @endforelse
        </div>
    </aside>

    {{-- 우: 채팅 --}}
    <section class="sr-chat">
        @if(!$configured)
            <div class="sr-warn">⚠ Claude API 키가 설정되지 않았습니다. 운영 <code>.env</code>의 <code>ANTHROPIC_API_KEY</code>를 확인하세요.</div>
        @endif

        @if(!$thread)
            <div class="sr-blank">
                <div class="sr-blank-in">
                    <div class="sr-logo">🤖</div>
                    <h3>SR 관리 콘솔</h3>
                    <p>왼쪽 ‘새 요청’을 눌러 대화를 시작하세요. 신규/수정 요청, 버그, 운영 문의를 올리고<br>이미지·PDF·로그·코드 파일을 첨부해 분석받을 수 있습니다.</p>
                    <div class="sr-model-badge">AI 어시스턴트</div>
                </div>
            </div>
        @else
            <div class="sr-head">
                <div class="sr-title" id="srTitle">{{ $thread->title }}</div>
                <div class="sr-head-r">
                    <span class="sr-model-badge">AI 어시스턴트</span>
                    <button type="button" class="sr-apply-btn" id="srApplyBtn" data-url="{{ route('admin.sr.apply', $thread) }}"><x-icon name="check" :size="15"/> 개발 반영 요청</button>
                    <form method="POST" action="{{ route('admin.sr.destroy', $thread) }}" onsubmit="return confirm('이 요청을 삭제할까요?')">@csrf @method('DELETE')
                        <button class="sr-del" type="submit" title="삭제"><x-icon name="trash" :size="15"/></button>
                    </form>
                </div>
            </div>

            @php($effApply = ($messages->count() && optional($messages->last())->role === 'user') ? 'none' : $thread->apply_status)
            <div class="sr-applybar" id="srApplyBar" data-status="{{ $effApply }}" style="{{ $effApply === 'none' ? 'display:none' : '' }}">
                <span class="sr-applybar-txt" id="srApplyTxt">
                    @if($effApply === 'requested')
                        🛠 반영 진행 중… {{ optional($thread->apply_requested_at)->format('Y-m-d H:i') }} · 처리가 끝나면 결과가 대화에 등록됩니다.
                    @elseif($effApply === 'done')
                        ✅ 반영 완료 처리됨
                    @endif
                </span>
                <span class="sr-applybar-act">
                    <button type="button" class="sr-mini" data-act="done">완료 처리</button>
                    <button type="button" class="sr-mini ghost" data-act="cancel">요청 취소</button>
                </span>
            </div>

            <div class="sr-msgs" id="srMsgs">
                @foreach($messages as $m)
                    <div class="sr-msg {{ $m->role }}">
                        <div class="sr-ava">{{ $m->role === 'user' ? '🙋' : '🤖' }}</div>
                        <div class="sr-bubble">
                            @foreach(($m->attachments ?? []) as $i => $a)
                                @php($url = route('admin.sr.attachment', [$thread->id, $m->id, $i]))
                                @if(str_starts_with($a['mime'] ?? '', 'image/'))
                                    <a href="{{ $url }}" target="_blank"><img class="sr-att-img" src="{{ $url }}" alt="{{ $a['name'] ?? '' }}"></a>
                                @else
                                    <a href="{{ $url }}" target="_blank" class="sr-att-file">📎 {{ $a['name'] ?? 'file' }}</a>
                                @endif
                            @endforeach
                            <div class="sr-body" data-raw="{{ $m->role === 'assistant' ? 'md' : 'text' }}">{{ $m->content }}</div>
                            @if($m->created_at)<div class="sr-time">{{ $m->created_at->format('Y-m-d H:i') }}</div>@endif
                        </div>
                    </div>
                @endforeach
            </div>

            <form class="sr-composer" id="srComposer" data-send="{{ route('admin.sr.message', $thread) }}">
                <div class="sr-atts" id="srAtts"></div>
                <div class="sr-input-row">
                    <label class="sr-clip" title="파일 첨부">
                        <input type="file" id="srFiles" multiple accept="image/*,.pdf,.txt,.csv,.md,.json,.xml,.log">
                        <x-icon name="plus" :size="18"/>
                    </label>
                    <textarea id="srText" rows="1" placeholder="요청 내용을 입력하세요. (Shift+Enter 줄바꿈, Enter 전송)"></textarea>
                    <button type="submit" id="srSend" class="sr-send"><x-icon name="arrow-right" :size="18"/></button>
                </div>
                <div class="sr-hint">첨부: 이미지·PDF·텍스트(코드/로그/CSV), 20MB 이하 · 이 콘솔은 직접 배포하지 않습니다(분석·초안 제공).</div>
            </form>
        @endif
    </section>
</div>

<style>
    .adm-body{padding:0 !important}
    .sr{display:grid;grid-template-columns:260px 1fr;height:calc(100vh - 62px);min-height:520px}
    .sr-list{border-right:1px solid var(--a-line,#e3e8f1);display:flex;flex-direction:column;background:#fafbfd;min-width:0}
    .sr-new{display:flex;align-items:center;justify-content:center;gap:6px;width:calc(100% - 24px);margin:12px;padding:11px;border:1px solid var(--a-line,#e3e8f1);background:#fff;border-radius:9px;font-weight:700;cursor:pointer;color:#123b26}
    .sr-new:hover{background:#f0f3ee}
    .sr-threads{overflow:auto;flex:1;padding:0 8px 12px}
    .sr-thread{display:flex;flex-direction:column;gap:2px;padding:10px 12px;border-radius:8px;text-decoration:none;color:#2a3344;margin-bottom:3px}
    .sr-thread:hover{background:#eef1f6}
    .sr-thread.on{background:#e7f0ea}
    .sr-thread .t{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sr-thread .d{font-size:11px;color:#97a0b8}
    .sr-empty-list{padding:24px 14px;color:#97a0b8;font-size:12.5px;line-height:1.7;text-align:center}

    .sr-chat{display:flex;flex-direction:column;min-width:0;background:#fff}
    .sr-warn{margin:12px 16px 0;padding:10px 12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;font-size:12.5px;color:#9a3412}
    .sr-blank{flex:1;display:flex;align-items:center;justify-content:center;text-align:center;color:#6b7280}
    .sr-blank-in h3{margin:12px 0 8px;font-size:18px;color:#1f2937}
    .sr-blank-in p{font-size:13.5px;line-height:1.7}
    .sr-logo{font-size:40px}
    .sr-model-badge{display:inline-block;margin-top:12px;font-size:11.5px;background:#eef2ff;color:#3730a3;border-radius:999px;padding:4px 11px;font-weight:700}

    .sr-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 18px;border-bottom:1px solid var(--a-line,#e3e8f1)}
    .sr-title{font-weight:700;font-size:14.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sr-head-r{display:flex;align-items:center;gap:10px;flex:none}
    .sr-del{border:1px solid var(--a-line,#e3e8f1);background:#fff;border-radius:7px;padding:6px 8px;cursor:pointer;color:#b4232a}
    .sr-del:hover{background:#fef2f2}
    .sr-apply-btn{display:inline-flex;align-items:center;gap:5px;border:1px solid #123b26;background:#123b26;color:#fff;border-radius:8px;padding:7px 12px;font-size:12.5px;font-weight:700;cursor:pointer;white-space:nowrap}
    .sr-apply-btn:hover{background:#0d2d1d}
    .sr-apply-btn.req{background:#fff;color:#123b26}
    .sr-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#e0892f;margin-right:6px;vertical-align:middle}
    .sr-dot.done{background:#22a35a}
    .sr-applybar{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:9px 18px;background:#fff7ed;border-bottom:1px solid #fde4c8;font-size:12.5px;color:#9a3412}
    .sr-applybar[data-status="done"]{background:#ecfdf3;border-bottom-color:#c7eed5;color:#15803d}
    .sr-applybar-act{display:flex;gap:6px;flex:none}
    .sr-mini{border:1px solid #d6a46a;background:#fff;color:#9a3412;border-radius:7px;padding:5px 10px;font-size:12px;cursor:pointer}
    .sr-mini.ghost{border-color:#e3e8f1;color:#6b7280}
    .sr-applybar[data-status="done"] .sr-mini{border-color:#9fd9b4;color:#15803d}

    .sr-msgs{flex:1;overflow:auto;padding:22px 18px;display:flex;flex-direction:column;gap:18px;background:#f7f8fa}
    .sr-msg{display:flex;gap:11px;max-width:900px;width:100%;margin:0 auto}
    .sr-msg.user{flex-direction:row-reverse}
    .sr-ava{flex:none;width:34px;height:34px;border-radius:9px;background:#fff;border:1px solid var(--a-line,#e3e8f1);display:flex;align-items:center;justify-content:center;font-size:17px}
    .sr-bubble{max-width:78%;background:#fff;border:1px solid var(--a-line,#e3e8f1);border-radius:13px;padding:13px 15px;box-shadow:0 1px 2px #0000000a}
    .sr-msg.user .sr-bubble{background:#123b26;color:#fff;border-color:#123b26}
    .sr-body{font-size:14px;line-height:1.72;word-break:break-word;white-space:pre-wrap}
    .sr-body.md{white-space:normal}
    .sr-msg.user .sr-body a{color:#ffe08a}
    .sr-time{font-size:10.5px;color:#9aa3b2;margin-top:7px}
    .sr-msg.user .sr-time{color:#cfe0d4}
    .sr-att-img{max-width:230px;max-height:230px;border-radius:8px;margin-bottom:8px;display:block;border:1px solid #0001}
    .sr-att-file{display:inline-block;background:#f1f3f9;border-radius:7px;padding:6px 10px;margin:0 6px 8px 0;font-size:12.5px;text-decoration:none;color:#334}
    .sr-msg.user .sr-att-file{background:#ffffff22;color:#fff}

    /* 마크다운 렌더 */
    .sr-body.md h1,.sr-body.md h2,.sr-body.md h3{margin:.6em 0 .35em;line-height:1.3}
    .sr-body.md h1{font-size:18px}.sr-body.md h2{font-size:16px}.sr-body.md h3{font-size:14.5px}
    .sr-body.md p{margin:.5em 0}
    .sr-body.md ul,.sr-body.md ol{margin:.5em 0;padding-left:1.4em}
    .sr-body.md li{margin:.2em 0}
    .sr-body.md code{background:#eef0f4;border-radius:4px;padding:1px 5px;font-size:12.5px;font-family:ui-monospace,Menlo,Consolas,monospace}
    .sr-body.md pre{background:#0d1117;color:#e6edf3;border-radius:9px;padding:13px 14px;overflow:auto;margin:.6em 0}
    .sr-body.md pre code{background:none;color:inherit;padding:0;font-size:12.5px;line-height:1.6}
    .sr-body.md a{color:#2563eb}
    .sr-body.md table{border-collapse:collapse;margin:.6em 0;font-size:13px}
    .sr-body.md th,.sr-body.md td{border:1px solid #d7dbe3;padding:6px 10px}
    .sr-body.md blockquote{border-left:3px solid #cbd5e1;margin:.5em 0;padding:.1em 12px;color:#54607a}

    .sr-composer{border-top:1px solid var(--a-line,#e3e8f1);padding:12px 16px 14px;background:#fff}
    .sr-atts{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px}
    .sr-atts:empty{display:none}
    .sr-chip{display:inline-flex;align-items:center;gap:6px;background:#f1f3f9;border-radius:8px;padding:5px 9px;font-size:12px;color:#334}
    .sr-chip b{max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600}
    .sr-chip button{border:0;background:none;cursor:pointer;color:#8a93a6;font-size:14px;line-height:1}
    .sr-input-row{display:flex;align-items:flex-end;gap:9px;border:1px solid var(--a-line,#e3e8f1);border-radius:13px;padding:7px 8px 7px 10px;background:#fff}
    .sr-input-row:focus-within{border-color:#123b26}
    .sr-clip{flex:none;width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#5b6472;background:#f3f5f9}
    .sr-clip:hover{background:#e9edf3}
    .sr-clip input{display:none}
    #srText{flex:1;border:0;outline:0;resize:none;font-size:14px;line-height:1.6;max-height:180px;padding:7px 2px;font-family:inherit;background:none}
    .sr-send{flex:none;width:38px;height:38px;border:0;border-radius:10px;background:#123b26;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center}
    .sr-send:disabled{opacity:.45;cursor:default}
    .sr-hint{font-size:11px;color:#9aa3b2;margin-top:8px;text-align:center}
    .sr-typing{display:inline-flex;gap:4px;align-items:center;color:#8a93a6;font-size:13px}
    .sr-typing i{width:6px;height:6px;border-radius:50%;background:#b7bfce;display:inline-block;animation:srb 1s infinite}
    .sr-typing i:nth-child(2){animation-delay:.15s}.sr-typing i:nth-child(3){animation-delay:.3s}
    @keyframes srb{0%,60%,100%{opacity:.3}30%{opacity:1}}
    @media(max-width:860px){.sr{grid-template-columns:1fr;height:auto}.sr-list{display:none}}
</style>

<script>
(function(){
    var root=document.getElementById('sr'); if(!root) return;
    var csrf=document.querySelector('meta[name="csrf-token"]').content;

    // ===== 초경량 마크다운 렌더 (XSS 안전: 먼저 이스케이프) =====
    function esc(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
    function inline(s){
        return s
            .replace(/`([^`]+)`/g,function(_,c){return '<code>'+c+'</code>';})
            .replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>')
            .replace(/(^|[^*])\*([^*]+)\*/g,'$1<em>$2</em>')
            .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,'<a href="$2" target="_blank" rel="noopener">$1</a>');
    }
    function renderMd(src){
        var lines=esc(src).split(/\r?\n/), out=[], i=0;
        while(i<lines.length){
            var ln=lines[i];
            if(/^```/.test(ln)){
                var lang=ln.replace(/^```/,'').trim(), buf=[]; i++;
                while(i<lines.length && !/^```/.test(lines[i])){ buf.push(lines[i]); i++; }
                i++; out.push('<pre><code>'+buf.join('\n')+'</code></pre>'); continue;
            }
            var h=ln.match(/^(#{1,3})\s+(.*)$/);
            if(h){ out.push('<h'+h[1].length+'>'+inline(h[2])+'</h'+h[1].length+'>'); i++; continue; }
            if(/^\s*[-*]\s+/.test(ln)){
                var items=[]; while(i<lines.length && /^\s*[-*]\s+/.test(lines[i])){ items.push('<li>'+inline(lines[i].replace(/^\s*[-*]\s+/,''))+'</li>'); i++; }
                out.push('<ul>'+items.join('')+'</ul>'); continue;
            }
            if(/^\s*\d+\.\s+/.test(ln)){
                var oi=[]; while(i<lines.length && /^\s*\d+\.\s+/.test(lines[i])){ oi.push('<li>'+inline(lines[i].replace(/^\s*\d+\.\s+/,''))+'</li>'); i++; }
                out.push('<ol>'+oi.join('')+'</ol>'); continue;
            }
            if(/^\s*>\s?/.test(ln)){ out.push('<blockquote>'+inline(ln.replace(/^\s*>\s?/,''))+'</blockquote>'); i++; continue; }
            if(ln.trim()===''){ i++; continue; }
            var para=[ln]; i++;
            while(i<lines.length && lines[i].trim()!=='' && !/^(```|#{1,3}\s|\s*[-*]\s|\s*\d+\.\s|\s*>\s?)/.test(lines[i])){ para.push(lines[i]); i++; }
            out.push('<p>'+inline(para.join('<br>'))+'</p>');
        }
        return out.join('');
    }
    // 서버 렌더된 기존 메시지에 마크다운 적용
    function hydrate(scope){
        (scope||document).querySelectorAll('.sr-body[data-raw="md"]').forEach(function(el){
            if(el.dataset.done) return; el.innerHTML=renderMd(el.textContent); el.dataset.done='1';
        });
    }
    hydrate();

    var msgs=document.getElementById('srMsgs');
    function scrollBottom(){ if(msgs) msgs.scrollTop=msgs.scrollHeight; }
    scrollBottom();

    // ===== 개발 반영 요청 =====
    (function(){
        var btn=document.getElementById('srApplyBtn'), bar=document.getElementById('srApplyBar'), txt=document.getElementById('srApplyTxt');
        if(!btn) return;
        function paint(status, time){
            if(status==='none'){ bar.style.display='none'; btn.classList.remove('req'); btn.innerHTML='<svg width="15" height="15"><use href="#i-check"/></svg> 개발 반영 요청'; return; }
            bar.style.display=''; bar.setAttribute('data-status',status); btn.classList.add('req');
            if(status==='requested'){ btn.innerHTML='🛠 반영 중'; txt.textContent='🛠 반영 진행 중… '+(time||'')+' · 처리가 끝나면 결과가 대화에 등록됩니다.'; }
            else if(status==='done'){ btn.innerHTML='✅ 반영 완료'; txt.textContent='✅ 반영 완료 처리됨'; }
        }
        function call(action, note){
            var fd=new FormData(); fd.append('action',action); if(note) fd.append('note',note);
            btn.disabled=true;
            fetch(btn.dataset.url,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:fd})
                .then(function(r){return r.json();})
                .then(function(d){ btn.disabled=false; if(d.ok){ paint(d.apply_status, d.requested_at);
                    var on=document.querySelector('.sr-thread.on .t'); // 좌측 점 갱신
                    if(on){ var dot=on.querySelector('.sr-dot'); if(dot) dot.remove();
                        if(d.apply_status!=='none'){ var i=document.createElement('i'); i.className='sr-dot'+(d.apply_status==='done'?' done':''); on.insertBefore(i,on.firstChild); } }
                    // 반영 요청 시 대화에 '진행 중' 안내 표시
                    if(action==='request'){
                        var mw=document.getElementById('srMsgs');
                        if(mw && !mw.querySelector('.sr-progress')){
                            var pw=document.createElement('div'); pw.className='sr-msg assistant sr-progress';
                            pw.innerHTML='<div class="sr-ava">🛠</div><div class="sr-bubble"><div class="sr-body"><span class="sr-typing"><i></i><i></i><i></i></span> 반영 진행 중입니다… 처리가 끝나면 결과가 이 대화에 등록됩니다. (잠시 후 새로고침해 확인)</div></div>';
                            mw.appendChild(pw); mw.scrollTop=mw.scrollHeight;
                        }
                    }
                } })
                .catch(function(){ btn.disabled=false; alert('처리 중 오류가 발생했습니다.'); });
        }
        // 로드 시 서버 상태 반영
        var init=bar?bar.getAttribute('data-status'):'none';
        if(init && init!=='none'){ btn.classList.add('req'); btn.innerHTML=(init==='done'?'✅ 반영 완료':'🛠 반영 중'); }

        btn.addEventListener('click',function(){
            if(btn.classList.contains('req')) return; // 이미 요청/완료 상태면 바의 버튼으로 조작
            if(!confirm('이 요청 내용을 개발 담당자에게 반영 요청하시겠어요?\n(담당자에게 앱 알림/이메일로 통지됩니다)')) return;
            call('request');
        });
        bar && bar.querySelectorAll('.sr-mini').forEach(function(b){
            b.addEventListener('click',function(){
                var act=b.dataset.act;
                if(act==='cancel' && !confirm('반영 요청을 취소할까요?')) return;
                call(act);
            });
        });
    })();

    var form=document.getElementById('srComposer'); if(!form) return;
    var text=document.getElementById('srText'), fileInput=document.getElementById('srFiles'),
        attsBox=document.getElementById('srAtts'), sendBtn=document.getElementById('srSend');
    var files=[];

    text.addEventListener('input',function(){ text.style.height='auto'; text.style.height=Math.min(text.scrollHeight,180)+'px'; });
    text.addEventListener('keydown',function(e){ if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); form.requestSubmit(); } });

    fileInput.addEventListener('change',function(){
        for(var k=0;k<fileInput.files.length;k++){ if(files.length<8) files.push(fileInput.files[k]); }
        fileInput.value=''; renderChips();
    });
    function renderChips(){
        attsBox.innerHTML='';
        files.forEach(function(f,idx){
            var c=document.createElement('span'); c.className='sr-chip';
            c.innerHTML='<b></b><button type="button">✕</button>';
            c.querySelector('b').textContent=f.name;
            c.querySelector('button').addEventListener('click',function(){ files.splice(idx,1); renderChips(); });
            attsBox.appendChild(c);
        });
    }

    function bubble(role, content, atts){
        var wrap=document.createElement('div'); wrap.className='sr-msg '+role;
        var ava=document.createElement('div'); ava.className='sr-ava'; ava.textContent=role==='user'?'🙋':'🤖';
        var b=document.createElement('div'); b.className='sr-bubble';
        (atts||[]).forEach(function(a){
            if(a.image){ var link=document.createElement('a'); link.href=a.url; link.target='_blank';
                var im=document.createElement('img'); im.className='sr-att-img'; im.src=a.url; link.appendChild(im); b.appendChild(link); }
            else { var fl=document.createElement('a'); fl.className='sr-att-file'; fl.href=a.url; fl.target='_blank'; fl.textContent='📎 '+a.name; b.appendChild(fl); }
        });
        var body=document.createElement('div'); body.className='sr-body'+(role==='assistant'?' md':'');
        if(role==='assistant'){ body.innerHTML=renderMd(content); } else { body.textContent=content; }
        b.appendChild(body);
        wrap.appendChild(ava); wrap.appendChild(b); msgs.appendChild(wrap); scrollBottom();
        return wrap;
    }

    form.addEventListener('submit',function(e){
        e.preventDefault();
        var content=text.value.trim();
        if(content==='' && files.length===0) return;

        var fd=new FormData(); fd.append('content',content);
        files.forEach(function(f){ fd.append('files[]',f); });

        // 낙관적 렌더 (이미지는 로컬 미리보기 URL 사용 — 전송 직후 깨짐 방지)
        var localAtts=files.map(function(f){ return {name:f.name, image:/^image\//.test(f.type), url:URL.createObjectURL(f)}; });
        bubble('user', content, localAtts);
        text.value=''; text.style.height='auto'; files=[]; renderChips();
        sendBtn.disabled=true;

        var typ=document.createElement('div'); typ.className='sr-msg assistant';
        typ.innerHTML='<div class="sr-ava">🤖</div><div class="sr-bubble"><span class="sr-typing"><i></i><i></i><i></i></span></div>';
        msgs.appendChild(typ); scrollBottom();

        fetch(form.dataset.send,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:fd})
            .then(function(r){ return r.json().then(function(d){ return {ok:r.ok,d:d}; }); })
            .then(function(res){
                typ.remove(); sendBtn.disabled=false;
                if(!res.ok){ bubble('assistant','⚠ '+(res.d.error||'오류가 발생했습니다.')); return; }
                bubble('assistant', res.d.assistant.content);
                // 제목/목록 갱신
                var tt=document.getElementById('srTitle'); if(tt && res.d.title) tt.textContent=res.d.title;
                var on=document.querySelector('.sr-thread.on .t'); if(on && res.d.title) on.textContent=res.d.title;
                // 새 지시가 들어왔으니 '개발 반영 요청'을 다시 눌러 재요청할 수 있게 복귀
                var reBtn=document.getElementById('srApplyBtn');
                if(reBtn){ reBtn.classList.remove('req'); reBtn.innerHTML='<svg width="15" height="15"><use href="#i-check"/></svg> 개발 반영 요청'; }
                var reBar=document.getElementById('srApplyBar');
                if(reBar){ reBar.style.display='none'; reBar.setAttribute('data-status','none'); }
            })
            .catch(function(){ typ.remove(); sendBtn.disabled=false; bubble('assistant','⚠ 네트워크 오류. 다시 시도해 주세요.'); });
    });
})();
</script>
@endsection
