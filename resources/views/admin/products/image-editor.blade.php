@extends('layouts.admin')
@section('title', '대표이미지 편집')
@section('heading', '대표이미지 편집 · '.$product->name)

@section('content')
<div class="adm-card" style="max-width:760px">
    <div style="padding:20px">
        <div style="display:flex;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:320px">
                <div style="background:#f4f6fa;border:1px solid var(--a-line);border-radius:10px;padding:12px;text-align:center;position:relative">
                    <canvas id="cv" style="max-width:100%;background:#fff;border-radius:6px;cursor:crosshair"></canvas>
                    <div id="cropBox" style="position:absolute;border:2px dashed #ff6b00;background:rgba(255,107,0,.12);display:none;pointer-events:none"></div>
                </div>
            </div>
            <div style="width:230px">
                <div class="afield"><label>회전</label>
                    <div style="display:flex;gap:6px;margin-bottom:8px">
                        <button type="button" class="abtn abtn-ghost abtn-sm" onclick="rot(-90)">⟲ 90°</button>
                        <button type="button" class="abtn abtn-ghost abtn-sm" onclick="rot(90)">⟳ 90°</button>
                    </div>
                    <input type="range" id="fine" min="-45" max="45" value="0" style="width:100%" oninput="render()">
                    <div class="ahint" id="fineV">미세 0°</div>
                </div>
                <div class="afield"><label>밝기 <span id="brV">100%</span></label>
                    <input type="range" id="br" min="50" max="150" value="100" style="width:100%" oninput="render()"></div>
                <div class="afield"><label>대비 <span id="ctV">100%</span></label>
                    <input type="range" id="ct" min="50" max="150" value="100" style="width:100%" oninput="render()"></div>
                <div class="afield">
                    <button type="button" class="abtn abtn-ghost abtn-sm" id="cropBtn" onclick="toggleCrop()">✂ 자르기 영역 지정</button>
                    <button type="button" class="abtn abtn-ghost abtn-sm" onclick="applyCrop()">적용</button>
                    <button type="button" class="abtn abtn-ghost abtn-sm" onclick="resetAll()">초기화</button>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.products.image.save', $product) }}" style="margin-top:18px;display:flex;gap:10px" onsubmit="return prep()">
            @csrf
            <input type="hidden" name="image" id="imgData">
            <a href="{{ route('admin.products.edit', $product) }}" class="abtn abtn-ghost">취소</a>
            <button class="abtn abtn-pri" style="flex:1;justify-content:center">편집본 저장</button>
        </form>
    </div>
</div>

<script>
var cv=document.getElementById('cv'), ctx=cv.getContext('2d');
var img=new Image(); img.crossOrigin='anonymous';
var angle=0, crop=null, cropping=false, cs=null;
img.onload=function(){ resetAll(); };
img.src=@json(\App\Support\Media::url($product->thumbnail));

function baseDims(){ var a=(angle%360+360)%360; return (a===90||a===270)?{w:img.height,h:img.width}:{w:img.width,h:img.height}; }
function render(){
    var fine=+document.getElementById('fine').value, br=+document.getElementById('br').value, ct=+document.getElementById('ct').value;
    document.getElementById('fineV').textContent='미세 '+fine+'°'; document.getElementById('brV').textContent=br+'%'; document.getElementById('ctV').textContent=ct+'%';
    var d=baseDims(); var maxW=460, scale=Math.min(1,maxW/d.w); cv.width=d.w*scale; cv.height=d.h*scale;
    ctx.save(); ctx.clearRect(0,0,cv.width,cv.height);
    ctx.filter='brightness('+br+'%) contrast('+ct+'%)';
    ctx.translate(cv.width/2,cv.height/2); ctx.rotate((angle+fine)*Math.PI/180); ctx.scale(scale,scale);
    ctx.drawImage(img,-img.width/2,-img.height/2); ctx.restore();
}
function rot(d){ angle=(angle+d)%360; render(); }
function resetAll(){ angle=0; crop=null; cropping=false; document.getElementById('cropBox').style.display='none';
    document.getElementById('fine').value=0; document.getElementById('br').value=100; document.getElementById('ct').value=100; render(); }

// 자르기: 캔버스 위 드래그로 영역 지정
function toggleCrop(){ cropping=!cropping; document.getElementById('cropBtn').style.background=cropping?'#ffe0c4':''; }
var box=document.getElementById('cropBox');
cv.addEventListener('mousedown',function(e){ if(!cropping)return; var r=cv.getBoundingClientRect(); cs={x:e.clientX-r.left,y:e.clientY-r.top}; });
cv.addEventListener('mousemove',function(e){ if(!cropping||!cs)return; var r=cv.getBoundingClientRect(); var x=e.clientX-r.left,y=e.clientY-r.top;
    var L=Math.min(cs.x,x),T=Math.min(cs.y,y),W=Math.abs(x-cs.x),H=Math.abs(y-cs.y);
    box.style.display='block'; box.style.left=(cv.offsetLeft+L)+'px'; box.style.top=(cv.offsetTop+T)+'px'; box.style.width=W+'px'; box.style.height=H+'px';
    crop={x:L/cv.width,y:T/cv.height,w:W/cv.width,h:H/cv.height}; });
window.addEventListener('mouseup',function(){ cs=null; });
function applyCrop(){ if(!crop||crop.w<0.02||crop.h<0.02){ alert('자를 영역을 드래그로 지정하세요.'); return; }
    var sx=crop.x*cv.width, sy=crop.y*cv.height, sw=crop.w*cv.width, sh=crop.h*cv.height;
    var tmp=document.createElement('canvas'); tmp.width=sw; tmp.height=sh; tmp.getContext('2d').drawImage(cv,sx,sy,sw,sh,0,0,sw,sh);
    img=new Image(); img.onload=function(){ resetAll(); }; img.src=tmp.toDataURL('image/png'); box.style.display='none'; cropping=false; crop=null;
    document.getElementById('cropBtn').style.background=''; }
function prep(){ document.getElementById('imgData').value=cv.toDataURL('image/jpeg',0.92); return true; }
</script>
@endsection
