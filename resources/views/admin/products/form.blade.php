@extends('layouts.admin')
@section('title', $product->exists ? '상품 수정' : '상품 등록')
@section('heading', $product->exists ? '상품 수정' : '상품 등록')

@section('content')
@php($statuses = ['on_sale'=>'판매중','soldout'=>'품절','closed'=>'판매중지','inbound'=>'입고예정'])
@php($val = fn($k,$d=null) => old($k, data_get($product,$k,$d)))

<form method="POST" action="{{ $product->exists ? route('admin.products.update',$product) : route('admin.products.store') }}" enctype="multipart/form-data" id="prodForm">
    @csrf
    @if($product->exists)@method('PUT')@endif

    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px;align-items:start">
        {{-- ===== 좌: 기본정보 + 상세설명 ===== --}}
        <div>
            <div class="adm-card"><div class="h">기본 정보</div><div style="padding:20px">
                <div class="afield"><label>상품명 <span style="color:#e0322d">*</span></label>
                    <input type="text" name="name" class="ainput" value="{{ $val('name') }}" required placeholder="예: 태국 남독마이 애플망고 5kg"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="afield"><label>카테고리 <span style="color:#e0322d">*</span></label>
                        <select name="category_id" class="aselect" required><option value="">선택</option>
                            @foreach($categories as $c)<option value="{{ $c->id }}" {{ (string)$val('category_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                        </select></div>
                    <div class="afield"><label>브랜드</label>
                        <select name="brand_id" class="aselect"><option value="">선택 안 함</option>
                            @foreach($brands as $b)<option value="{{ $b->id }}" {{ (string)$val('brand_id')===(string)$b->id?'selected':'' }}>{{ $b->name }}</option>@endforeach
                        </select></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="afield"><label>상품코드(SKU)</label><input type="text" name="code" class="ainput" value="{{ $val('code') }}"></div>
                    <div class="afield"><label>슬러그(URL)</label><input type="text" name="slug" class="ainput" value="{{ $val('slug') }}" placeholder="비우면 자동생성"></div>
                    <div class="afield"><label>판매단위</label><input type="text" name="unit" class="ainput" value="{{ $val('unit','BOX') }}" placeholder="BOX/EA/SET"></div>
                </div>
                <div class="afield"><label>짧은 설명(요약)</label><input type="text" name="summary" class="ainput" value="{{ $val('summary') }}" placeholder="원산지·등급·규격 한 줄 요약"></div>
            </div></div>

            <div class="adm-card"><div class="h">신선식품 속성</div><div style="padding:20px">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="afield"><label>원산지</label><input type="text" name="origin" class="ainput" value="{{ $val('origin') }}" placeholder="태국"></div>
                    <div class="afield"><label>품종</label><input type="text" name="variety" class="ainput" value="{{ $val('variety') }}"></div>
                    <div class="afield"><label>등급</label><input type="text" name="grade" class="ainput" value="{{ $val('grade') }}"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="afield"><label>제조/수입사</label><input type="text" name="maker" class="ainput" value="{{ $val('maker') }}"></div>
                    <div class="afield"><label>규격(박스)</label><input type="text" name="box_spec" class="ainput" value="{{ $val('box_spec') }}" placeholder="5kg/9~12과"></div>
                    <div class="afield"><label>중량(kg)</label><input type="number" step="0.1" name="weight_kg" class="ainput" value="{{ $val('weight_kg') }}"></div>
                </div>
            </div></div>

            <div class="adm-card"><div class="h">상세 설명</div><div style="padding:20px">
                <div class="aeditor-wrap"><div class="aeditor" id="descEditor">{!! \App\Support\Media::html($val('description')) !!}</div></div>
                <input type="hidden" name="description" id="descInput" value="{{ $val('description') }}">
                <div class="ahint" style="margin-top:6px">이미지는 툴바 🖼 · 붙여넣기 · 드래그로 삽입됩니다.</div>
            </div></div>

            {{-- ===== 옵션 ===== --}}
            <div class="adm-card"><div class="h">상품 옵션</div><div style="padding:20px">
                <div class="ahint" style="margin-bottom:10px">단일레벨 옵션(옵션구분이 같으면 하나의 선택박스로 묶입니다). 추가금액은 ± 입력 가능.</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
                    <button type="button" class="abtn abtn-ghost abtn-sm" onclick="fillPreset('규격','5kg,10kg')">규격 5/10kg</button>
                    <button type="button" class="abtn abtn-ghost abtn-sm" onclick="fillPreset('과수','8과,9과,10과')">과수 8/9/10</button>
                    <button type="button" class="abtn abtn-ghost abtn-sm" onclick="fillPreset('포장','일반,선물박스,보자기')">포장</button>
                </div>
                <table class="atable" id="optTable" style="font-size:13px">
                    <thead><tr><th style="width:130px">옵션구분</th><th>선택지</th><th style="width:110px">추가금액</th><th style="width:80px">재고</th><th style="width:50px">사용</th><th style="width:40px"></th></tr></thead>
                    <tbody id="optBody">
                        @foreach(old('options', $product->options->map(fn($o)=>$o->only(['id','group_name','name','extra_price','stock','is_active'])) ?? []) as $i=>$o)
                        <tr>
                            <td><input type="hidden" name="options[{{ $i }}][id]" value="{{ $o['id'] ?? '' }}"><input type="text" name="options[{{ $i }}][group_name]" class="ainput" value="{{ $o['group_name'] ?? '' }}"></td>
                            <td><input type="text" name="options[{{ $i }}][name]" class="ainput" value="{{ $o['name'] ?? '' }}"></td>
                            <td><input type="number" name="options[{{ $i }}][extra_price]" class="ainput" value="{{ $o['extra_price'] ?? 0 }}"></td>
                            <td><input type="number" name="options[{{ $i }}][stock]" class="ainput" value="{{ $o['stock'] ?? 0 }}"></td>
                            <td style="text-align:center"><input type="checkbox" name="options[{{ $i }}][is_active]" value="1" {{ ($o['is_active'] ?? true) ? 'checked' : '' }}></td>
                            <td style="text-align:center"><button type="button" class="abtn abtn-ghost abtn-sm" onclick="this.closest('tr').remove()">×</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="button" class="abtn abtn-ghost abtn-sm" style="margin-top:10px" onclick="addOptionRow()"><x-icon name="plus" :size="14"/> 옵션 추가</button>
            </div></div>
        </div>

        {{-- ===== 우: 가격/재고/노출/이미지 ===== --}}
        <div>
            <div class="adm-card"><div class="h">가격 · 재고</div><div style="padding:20px">
                <div class="afield"><label>판매가(원) <span style="color:#e0322d">*</span></label><input type="number" name="price" class="ainput" value="{{ $val('price') }}" required></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="afield"><label>기본 도매가</label><input type="number" name="member_price" class="ainput" value="{{ $val('member_price') }}"></div>
                    <div class="afield"><label>도매가(승인회원)</label><input type="number" name="wholesale_price" class="ainput" value="{{ $val('wholesale_price') }}"></div>
                </div>
                <div class="afield"><label>매입가(참고·비노출)</label><input type="number" name="cost" class="ainput" value="{{ $val('cost') }}"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="afield"><label>과세구분 <span style="color:#e0322d">*</span></label>
                        <select name="tax_type" class="aselect">
                            <option value="exempt" {{ $val('tax_type','exempt')==='exempt'?'selected':'' }}>면세</option>
                            <option value="taxable" {{ $val('tax_type')==='taxable'?'selected':'' }}>과세</option>
                        </select></div>
                    <div class="afield"><label>판매상태</label>
                        <select name="sale_status" class="aselect">@foreach($statuses as $k=>$v)<option value="{{ $k }}" {{ $val('sale_status','on_sale')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="afield"><label>재고 <span style="color:#e0322d">*</span></label><input type="number" name="stock" class="ainput" value="{{ $val('stock',0) }}" required></div>
                    <div class="afield"><label>최소주문(MOQ)</label><input type="number" name="moq" class="ainput" value="{{ $val('moq',1) }}"></div>
                </div>
            </div></div>

            <div class="adm-card"><div class="h">노출 설정</div><div style="padding:20px">
                @foreach(['is_active'=>'판매중(노출)','is_featured'=>'추천상품','is_best'=>'베스트','is_new'=>'신상품','is_quote'=>'가격문의(가격 숨김)'] as $k=>$lbl)
                    <label style="display:inline-flex;align-items:center;gap:7px;font-weight:600;margin:0 14px 8px 0;cursor:pointer">
                        <input type="checkbox" name="{{ $k }}" value="1" {{ $val($k, $k==='is_active') ? 'checked' : '' }} style="width:16px;height:16px"> {{ $lbl }}
                    </label>
                @endforeach
                <div class="ahint" style="margin-top:2px">‘가격문의’ 체크 시 가격 대신 <b>가격문의</b>가 표시되고 담기 대신 견적문의로 안내됩니다.</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:8px">
                    <div class="afield"><label>커스텀 뱃지</label><input type="text" name="badge" class="ainput" value="{{ $val('badge') }}" placeholder="예: 기획"></div>
                    <div class="afield"><label>정렬순서</label><input type="number" name="sort_order" class="ainput" value="{{ $val('sort_order') }}"></div>
                </div>
            </div></div>

            <div class="adm-card"><div class="h">대표 이미지</div><div style="padding:20px">
                <div style="display:flex;align-items:center;gap:14px">
                    <div style="width:88px;height:88px;border-radius:10px;background:#f7f9fc;overflow:hidden;flex:none;display:flex;align-items:center;justify-content:center;color:#c7cedd">
                        <img id="mainPrev" src="{{ $product->thumbnail ? \App\Support\Media::url($product->thumbnail) : '' }}" style="width:100%;height:100%;object-fit:cover;{{ $product->thumbnail ? '' : 'display:none' }}">
                        <span id="mainPh" style="{{ $product->thumbnail ? 'display:none' : '' }}"><x-icon name="box" :size="26"/></span>
                    </div>
                    <input type="file" name="thumbnail" accept="image/*" class="ainput" style="padding:8px" onchange="prev(this,'mainPrev','mainPh')">
                </div>
                @if($product->exists && $product->thumbnail)
                    <a href="{{ route('admin.products.image', $product) }}" class="abtn abtn-ghost abtn-sm" style="margin-top:10px">✂ 이미지 편집(회전·자르기·밝기)</a>
                @endif
            </div></div>

            <div class="adm-card"><div class="h">갤러리 이미지</div><div style="padding:20px">
                @if($product->exists && $product->galleryImages->count())
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px">
                        @foreach($product->galleryImages as $img)
                            <label style="position:relative;cursor:pointer" title="체크 시 저장할 때 삭제">
                                <img src="{{ $img->url }}" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px" class="galimg">
                                <input type="checkbox" name="remove_images[]" value="{{ $img->id }}" style="position:absolute;top:4px;right:4px" onchange="this.previousElementSibling.style.opacity=this.checked?0.3:1">
                            </label>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="gallery[]" accept="image/*" multiple class="ainput" style="padding:8px">
                <div class="ahint">여러 장 선택 가능 · 상단 갤러리 슬라이드에 노출</div>
            </div></div>

            <div class="adm-card"><div class="h">상세 이미지</div><div style="padding:20px">
                @if($product->exists && $product->detailImages->count())
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px">
                        @foreach($product->detailImages as $img)
                            <label style="position:relative;cursor:pointer">
                                <img src="{{ $img->url }}" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px">
                                <input type="checkbox" name="remove_images[]" value="{{ $img->id }}" style="position:absolute;top:4px;right:4px" onchange="this.previousElementSibling.style.opacity=this.checked?0.3:1">
                            </label>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="detail_images[]" accept="image/*" multiple class="ainput" style="padding:8px">
                <div class="ahint">상세페이지 하단에 세로로 순서대로 노출</div>
            </div></div>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:18px;max-width:400px">
        <a href="{{ route('admin.products.index') }}" class="abtn abtn-ghost" style="flex:none">취소</a>
        <button class="abtn abtn-pri" style="flex:1;justify-content:center">{{ $product->exists ? '수정 저장' : '상품 등록' }}</button>
    </div>
</form>

{{-- Quill --}}
<link rel="stylesheet" href="{{ asset('vendor/quill/quill.snow.css') }}">
<style>
    .aeditor-wrap{border:1px solid var(--a-line,#e3e8f1);border-radius:8px;overflow:hidden;background:#fff}
    .aeditor-wrap:focus-within{border-color:var(--a-navy,#ff6b00);box-shadow:0 0 0 3px rgba(255,107,0,.12)}
    .aeditor-wrap .ql-toolbar{border:0;border-bottom:1px solid var(--a-line,#e3e8f1);background:#fbfcfe}
    .aeditor-wrap .ql-container{border:0;font-size:14px;font-family:inherit}
    .aeditor-wrap .ql-editor{min-height:300px;line-height:1.7}
    .aeditor-wrap .ql-editor img{max-width:100%;height:auto}
    #optTable td{padding:5px 6px} #optTable .ainput{padding:7px 9px}
</style>
<script src="{{ asset('vendor/quill/quill.min.js') }}"></script>
<script>
(function(){
    var host=document.getElementById('descEditor'), input=document.getElementById('descInput');
    var csrf=document.querySelector('meta[name="csrf-token"]').content;
    var uploadUrl=@json(route('admin.products.editor.upload'));
    var q=new Quill(host,{theme:'snow',placeholder:'상품 상세 설명을 입력하세요.',modules:{toolbar:[
        [{header:[false,2,3,4]}],['bold','italic','underline','strike'],[{color:[]},{background:[]}],
        [{list:'ordered'},{list:'bullet'}],[{align:[]}],['blockquote','link','image'],['clean']]}});

    function upload(file){var fd=new FormData();fd.append('file',file);
        fetch(uploadUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:fd})
        .then(r=>r.json()).then(d=>{if(!d.url)return;var r=q.getSelection(true)||{index:q.getLength()};
            q.insertEmbed(r.index,'image',d.url,'user');q.setSelection(r.index+1);})
        .catch(()=>alert('이미지 업로드 실패'));}
    q.getModule('toolbar').addHandler('image',function(){var i=document.createElement('input');i.type='file';i.accept='image/*';
        i.onchange=function(){if(i.files[0])upload(i.files[0]);};i.click();});
    q.root.addEventListener('paste',function(e){var it=(e.clipboardData||{}).items||[];for(var k=0;k<it.length;k++){
        if(it[k].type&&it[k].type.indexOf('image')===0){e.preventDefault();upload(it[k].getAsFile());}}});
    q.root.addEventListener('drop',function(e){if(e.dataTransfer&&e.dataTransfer.files.length){e.preventDefault();
        for(var k=0;k<e.dataTransfer.files.length;k++){if(e.dataTransfer.files[k].type.indexOf('image')===0)upload(e.dataTransfer.files[k]);}}});

    document.getElementById('prodForm').addEventListener('submit',function(){
        input.value=(q.getText().trim()===''&&!q.root.querySelector('img'))?'':q.root.innerHTML;});
})();

// 이미지 미리보기
function prev(inp,imgId,phId){var f=inp.files[0];if(!f)return;var im=document.getElementById(imgId);
    im.src=URL.createObjectURL(f);im.style.display='block';var ph=document.getElementById(phId);if(ph)ph.style.display='none';}

// 옵션 행 추가
var optIdx={{ is_array(old('options')) ? count(old('options')) : $product->options->count() }};
function addOptionRow(g,n,price,stock){
    var i=optIdx++;var tb=document.getElementById('optBody');var tr=document.createElement('tr');
    tr.innerHTML='<td><input type="hidden" name="options['+i+'][id]" value=""><input type="text" name="options['+i+'][group_name]" class="ainput" value="'+(g||'')+'"></td>'+
        '<td><input type="text" name="options['+i+'][name]" class="ainput" value="'+(n||'')+'"></td>'+
        '<td><input type="number" name="options['+i+'][extra_price]" class="ainput" value="'+(price||0)+'"></td>'+
        '<td><input type="number" name="options['+i+'][stock]" class="ainput" value="'+(stock||0)+'"></td>'+
        '<td style="text-align:center"><input type="checkbox" name="options['+i+'][is_active]" value="1" checked></td>'+
        '<td style="text-align:center"><button type="button" class="abtn abtn-ghost abtn-sm" onclick="this.closest(\'tr\').remove()">×</button></td>';
    tb.appendChild(tr);
}
function fillPreset(group,csv){csv.split(',').forEach(function(n){addOptionRow(group,n.trim(),0,0);});}
</script>
@endsection
