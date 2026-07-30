@extends('layouts.seller')
@section('title', $product->exists ? '상품 수정' : '상품 등록')
@section('heading', $product->exists ? '상품 수정' : '상품 등록')

@section('content')
@php($statuses = ['on_sale'=>'판매중','soldout'=>'품절','closed'=>'판매중지','inbound'=>'입고예정'])

<div class="adm-card" style="max-width:820px">
    <div class="h">{{ $product->exists ? $product->name : '새 상품 정보' }}</div>
    <form method="POST" action="{{ $product->exists ? route('seller.center.products.update', $product) : route('seller.center.products.store') }}" enctype="multipart/form-data" style="padding:22px">
        @csrf
        @if($product->exists)@method('PUT')@endif

        {{-- 이미지 --}}
        <div class="afield" style="margin:0 0 16px">
            <label>상품 이미지</label>
            <div style="display:flex;align-items:center;gap:14px">
                <div style="width:80px;height:80px;border-radius:10px;background:#f7f9fc;overflow:hidden;flex:none;display:flex;align-items:center;justify-content:center;color:#c7cedd">
                    @if($product->thumbnail)<img id="imgPrev" src="{{ $product->thumb_url }}" style="width:100%;height:100%;object-fit:cover">
                    @else<img id="imgPrev" src="" style="width:100%;height:100%;object-fit:cover;display:none"><span id="imgPh"><x-icon name="package" :size="24"/></span>@endif
                </div>
                <input type="file" name="image" accept="image/*" class="ainput" style="padding:8px" onchange="(function(e){var f=e.target.files[0];if(!f)return;var p=document.getElementById('imgPrev');p.src=URL.createObjectURL(f);p.style.display='block';var ph=document.getElementById('imgPh');if(ph)ph.style.display='none';})(event)">
            </div>
            <div style="color:#97a0b8;font-size:12px;margin-top:6px">JPG·PNG·WEBP · 최대 4MB · 정사각형 권장</div>
        </div>

        <div class="afield" style="margin:0 0 12px"><label>상품명 <span style="color:#e0322d">*</span></label>
            <input type="text" name="name" class="ainput" value="{{ old('name', $product->name) }}" required placeholder="예: 태국 남독마이 애플망고 5kg">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="afield" style="margin:0"><label>카테고리 <span style="color:#e0322d">*</span></label>
                <select name="category_id" class="aselect" required>
                    <option value="">선택</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ old('category_id', $product->category_id)==$c->id?'selected':'' }}>{{ $c->name }}</option>
                        @foreach($c->children as $ch)
                            <option value="{{ $ch->id }}" {{ old('category_id', $product->category_id)==$ch->id?'selected':'' }}>&nbsp;└ {{ $ch->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="afield" style="margin:0"><label>판매상태</label>
                <select name="sale_status" class="aselect">
                    @foreach($statuses as $k=>$v)<option value="{{ $k }}" {{ old('sale_status', $product->sale_status)===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px">
            <div class="afield" style="margin:0"><label>원산지</label><input type="text" name="origin" class="ainput" value="{{ old('origin', $product->origin) }}" placeholder="태국"></div>
            <div class="afield" style="margin:0"><label>품종</label><input type="text" name="variety" class="ainput" value="{{ old('variety', $product->variety) }}" placeholder="남독마이"></div>
            <div class="afield" style="margin:0"><label>등급</label><input type="text" name="grade" class="ainput" value="{{ old('grade', $product->grade) }}" placeholder="특"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="afield" style="margin:0"><label>규격(박스)</label><input type="text" name="box_spec" class="ainput" value="{{ old('box_spec', $product->box_spec) }}" placeholder="5kg/9~12과"></div>
            <div class="afield" style="margin:0"><label>중량(kg)</label><input type="number" step="0.1" name="weight_kg" class="ainput" value="{{ old('weight_kg', $product->weight_kg) }}"></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;margin-top:12px">
            <div class="afield" style="margin:0"><label>판매가(원) <span style="color:#e0322d">*</span></label><input type="number" name="price" class="ainput" value="{{ old('price', $product->price) }}" required></div>
            <div class="afield" style="margin:0"><label>도매가(원)</label><input type="number" name="wholesale_price" class="ainput" value="{{ old('wholesale_price', $product->wholesale_price) }}"></div>
            <div class="afield" style="margin:0"><label>재고 <span style="color:#e0322d">*</span></label><input type="number" name="stock" class="ainput" value="{{ old('stock', $product->stock ?? 0) }}" required></div>
            <div class="afield" style="margin:0"><label>최소주문(MOQ)</label><input type="number" name="moq" class="ainput" value="{{ old('moq', $product->moq ?? 1) }}"></div>
        </div>

        <div class="afield" style="margin-top:12px"><label>요약</label><input type="text" name="summary" class="ainput" value="{{ old('summary', $product->summary) }}" placeholder="원산지·등급·규격 한 줄 요약"></div>
        <div class="afield" style="margin-top:12px"><label>상세설명</label>
            <div class="aeditor-wrap"><div class="aeditor" id="ed_description">{!! old('description', $product->description) !!}</div></div>
            <input type="hidden" name="description" id="ed_val_description" value="{{ old('description', $product->description) }}">
        </div>

        <label style="display:inline-flex;align-items:center;gap:8px;font-weight:600;margin:14px 0 4px;cursor:pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->exists ? $product->is_active : true) ? 'checked' : '' }} style="width:16px;height:16px"> 판매 활성화(노출)
        </label>

        <div style="display:flex;gap:10px;margin-top:18px">
            <a href="{{ route('seller.center.products') }}" class="abtn abtn-ghost" style="flex:none">취소</a>
            <button class="abtn abtn-pri" style="flex:1;justify-content:center">{{ $product->exists ? '수정 저장' : '상품 등록' }}</button>
        </div>
    </form>
</div>

{{-- Quill 리치에디터 (상세설명) --}}
<link rel="stylesheet" href="{{ asset('vendor/quill/quill.snow.css') }}">
<style>
    .aeditor-wrap{border:1px solid var(--a-line,#e3e8f1);border-radius:8px;overflow:hidden;background:#fff}
    .aeditor-wrap:focus-within{border-color:var(--a-navy,#ff6b00);box-shadow:0 0 0 3px rgba(255,107,0,.12)}
    .aeditor-wrap .ql-toolbar{border:0;border-bottom:1px solid var(--a-line,#e3e8f1);background:#fbfcfe}
    .aeditor-wrap .ql-container{border:0;font-size:14px;font-family:inherit}
    .aeditor-wrap .ql-editor{min-height:260px;line-height:1.7}
    .aeditor-wrap .ql-editor img{max-width:100%;height:auto}
    .aeditor-wrap .ql-editor.ql-blank::before{color:#9aa5bd;font-style:normal}
</style>
<script src="{{ asset('vendor/quill/quill.min.js') }}"></script>
<script>
(function () {
    var host = document.getElementById('ed_description');
    var val  = document.getElementById('ed_val_description');
    if (!host || !val || !window.Quill) return;
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var uploadUrl = @json(route('seller.center.editor.upload'));

    var q = new Quill(host, {
        theme: 'snow',
        placeholder: '상품 상세 설명을 입력하세요. 이미지는 붙여넣거나 툴바에서 올릴 수 있습니다.',
        modules: { toolbar: [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ color: [] }, { background: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ align: [] }],
            ['link', 'image'],
            ['clean'],
        ] },
    });

    function uploadImage(file) {
        var fd = new FormData(); fd.append('file', file);
        fetch(uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.url) return;
                var range = q.getSelection(true) || { index: q.getLength() };
                q.insertEmbed(range.index, 'image', d.url, 'user');
                q.setSelection(range.index + 1);
            })
            .catch(function () { alert('이미지 업로드에 실패했습니다.'); });
    }
    // 툴바 이미지 버튼 → 파일선택 업로드
    q.getModule('toolbar').addHandler('image', function () {
        var input = document.createElement('input');
        input.type = 'file'; input.accept = 'image/*';
        input.onchange = function () { if (input.files[0]) uploadImage(input.files[0]); };
        input.click();
    });
    // 붙여넣기 이미지 업로드
    q.root.addEventListener('paste', function (e) {
        var items = (e.clipboardData || {}).items || [];
        for (var i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image') === 0) {
                e.preventDefault(); uploadImage(items[i].getAsFile());
            }
        }
    });

    // 제출 시 내용 동기화 (빈 문서는 빈 문자열)
    host.closest('form').addEventListener('submit', function () {
        val.value = (q.getText().trim() === '' && !q.root.querySelector('img')) ? '' : q.root.innerHTML;
    });
})();
</script>
@endsection
