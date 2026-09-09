@extends('layouts.admin')
@section('title', $recipe->exists ? '레시피 수정' : '레시피 등록')
@section('heading', $recipe->exists ? '레시피 수정' : '레시피 등록')

@section('content')
@php($val = fn($k,$d=null) => old($k, data_get($recipe,$k,$d)))

<form method="POST" action="{{ $recipe->exists ? route('admin.recipes.update',$recipe) : route('admin.recipes.store') }}" enctype="multipart/form-data" id="recipeForm">
    @csrf
    @if($recipe->exists)@method('PUT')@endif

    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px;align-items:start">
        {{-- 좌: 본문 --}}
        <div>
            <div class="adm-card"><div class="h">기본 정보</div><div style="padding:20px;display:grid;gap:14px">
                <label>카테고리 <span style="color:#e0322d">*</span>
                    <select name="recipe_category_id" class="ainput" required>
                        <option value="">선택</option>
                        @foreach($categories as $c)<option value="{{ $c->id }}" @selected($val('recipe_category_id')==$c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                    @if(!$categories->count())<div class="ahint" style="color:#e0322d">먼저 <a href="{{ route('admin.index','recipe_categories') }}">레시피 카테고리</a>를 추가하세요.</div>@endif
                </label>
                <label>제목 <span style="color:#e0322d">*</span><input type="text" name="title" class="ainput" value="{{ $val('title') }}" required></label>
                <label>슬러그(URL)<input type="text" name="slug" class="ainput" value="{{ $val('slug') }}" placeholder="비우면 제목 기준 자동 생성(한글 가능)"></label>
                <label>요약(목록·검색 설명)<input type="text" name="summary" class="ainput" value="{{ $val('summary') }}" maxlength="300"></label>
            </div></div>

            <div class="adm-card"><div class="h">본문 (글 + 사진)</div><div style="padding:20px">
                <div class="aeditor-wrap"><div class="aeditor" id="descEditor">{!! \App\Support\Media::html($val('body')) !!}</div></div>
                <input type="hidden" name="body" id="descInput" value="{{ $val('body') }}">
            </div></div>

            <div class="adm-card"><div class="h">레시피 정보(선택 · SEO 리치결과)</div><div style="padding:20px;display:grid;gap:14px">
                <label>조리 시간<input type="text" name="cook_time" class="ainput" value="{{ $val('cook_time') }}" placeholder="예: 20분"></label>
                <label>재료(줄바꿈으로 구분)<textarea name="ingredients" class="ainput" rows="4" placeholder="애플망고 2개&#10;우유 200ml&#10;꿀 1스푼">{{ $val('ingredients') }}</textarea></label>
                <label>태그(쉼표 구분)<input type="text" name="tags" class="ainput" value="{{ $val('tags') }}" placeholder="애플망고, 디저트, 여름"></label>
            </div></div>

            <div class="adm-card"><div class="h">SEO</div><div style="padding:20px;display:grid;gap:14px">
                <label>SEO 제목<input type="text" name="meta_title" class="ainput" value="{{ $val('meta_title') }}" placeholder="비우면 제목 사용"></label>
                <label>SEO 설명<textarea name="meta_description" class="ainput" rows="2" placeholder="비우면 요약 사용">{{ $val('meta_description') }}</textarea></label>
            </div></div>
        </div>

        {{-- 우: 노출/이미지 --}}
        <div>
            <div class="adm-card"><div class="h">노출</div><div style="padding:20px;display:grid;gap:12px">
                <label class="inline"><input type="checkbox" name="is_pinned" value="1" @checked($val('is_pinned',true))> 카테고리 상단 고정(공식 레시피)</label>
                <label class="inline"><input type="checkbox" name="hidden" value="1" @checked($val('status')==='hidden')> 숨김(비공개)</label>
                <button class="abtn abtn-primary abtn-block">{{ $recipe->exists ? '수정 저장' : '등록' }}</button>
                <a href="{{ route('admin.recipes.index') }}" class="abtn abtn-ghost abtn-block">목록</a>
            </div></div>

            <div class="adm-card"><div class="h">대표 이미지</div><div style="padding:20px">
                <img id="coverPrev" src="{{ $recipe->cover_image ? \App\Support\Media::url($recipe->cover_image) : '' }}" style="width:100%;border-radius:8px;margin-bottom:10px;{{ $recipe->cover_image ? '' : 'display:none' }}">
                <input type="file" name="cover" accept="image/*" class="ainput" style="padding:8px" onchange="prev(this,'coverPrev')">
                <div class="ahint">목록·공유(OG)·검색 썸네일로 사용</div>
            </div></div>

            <div class="adm-card"><div class="h">갤러리 이미지</div><div style="padding:20px">
                @if($recipe->exists && $recipe->images->count())
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px">
                        @foreach($recipe->images as $img)
                            <label style="position:relative;display:block">
                                <img src="{{ $img->url }}" style="width:100%;border-radius:6px">
                                <span style="position:absolute;top:4px;right:4px;background:#fff;border-radius:4px;padding:2px 4px;font-size:11px"><input type="checkbox" name="remove_images[]" value="{{ $img->id }}"> 삭제</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="gallery[]" accept="image/*" multiple class="ainput" style="padding:8px">
                <div class="ahint">상세 하단에 순서대로 노출</div>
            </div></div>

            <div class="adm-card"><div class="h">영상</div><div style="padding:20px;display:grid;gap:12px">
                <label>유튜브 링크<input type="text" name="video_url" class="ainput" value="{{ $val('video_url') }}" placeholder="https://youtu.be/... 또는 https://www.youtube.com/watch?v=..."></label>
                <div class="ahint">유튜브 링크를 넣으면 상세페이지에 영상이 재생됩니다.</div>
                @if($recipe->exists && $recipe->video_path)
                    <video src="{{ $recipe->video_file_url }}" controls style="width:100%;border-radius:8px"></video>
                    <label class="inline"><input type="checkbox" name="remove_video" value="1"> 업로드 영상 삭제</label>
                @endif
                <label>영상 파일 업로드(선택)<input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" class="ainput" style="padding:8px"></label>
                <div class="ahint">mp4/webm/mov · 최대 100MB. 서버 업로드 한도(post_max_size)를 넘으면 실패할 수 있습니다.</div>
            </div></div>
        </div>
    </div>
</form>

<link rel="stylesheet" href="{{ asset('vendor/quill/quill.snow.css') }}">
<style>
    .aeditor-wrap{border:1px solid var(--a-line,#e3e8f1);border-radius:8px;overflow:hidden;background:#fff}
    .aeditor-wrap .ql-container{border:0;font-size:14px;font-family:inherit}
    .aeditor-wrap .ql-editor{min-height:320px;line-height:1.7}
    .aeditor-wrap .ql-editor img{max-width:100%;height:auto}
    #recipeForm label{display:block;font-size:13px;font-weight:600;color:#33415c}
    #recipeForm .ainput{margin-top:6px}
    #recipeForm label.inline{display:flex;align-items:center;gap:8px;font-weight:500}
</style>
<script src="{{ asset('vendor/quill/quill.min.js') }}"></script>
<script>
(function(){
    var host=document.getElementById('descEditor'), input=document.getElementById('descInput');
    var csrf=document.querySelector('meta[name="csrf-token"]').content;
    var uploadUrl=@json(route('admin.recipes.editor.upload'));
    var q=new Quill(host,{theme:'snow',placeholder:'레시피 내용을 작성하세요. 사진도 넣을 수 있습니다.',modules:{toolbar:[
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
    document.getElementById('recipeForm').addEventListener('submit',function(){
        input.value=(q.getText().trim()===''&&!q.root.querySelector('img'))?'':q.root.innerHTML;});
})();
function prev(inp,imgId){var f=inp.files[0];if(!f)return;var im=document.getElementById(imgId);im.src=URL.createObjectURL(f);im.style.display='block';}
</script>
@endsection
