@extends('layouts.app')
@section('title', ($recipe->exists ? '레시피 수정' : '레시피 작성').' — 망고샵')

@section('content')
<div class="rcp container" style="max-width:720px;padding:24px 20px 48px">
    <style>
        .rcp h1{font-size:22px;margin:0 0 18px}
        .rcp form label{display:block;font-size:13.5px;font-weight:600;color:#33415c;margin-bottom:6px}
        .rcp form .f{margin-bottom:18px}
        .rcp form input,.rcp form select,.rcp form textarea{width:100%;border:1px solid #dcdcd4;border-radius:8px;padding:11px 12px;font-size:14px;font-family:inherit}
        .rcp form textarea{min-height:200px;line-height:1.7;resize:vertical}
        .rcp .thumbs{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px}
        .rcp .thumbs label{position:relative;display:block;margin:0}
        .rcp .thumbs img{width:100%;border-radius:6px}
        .rcp .thumbs .rm{position:absolute;top:4px;right:4px;background:#fff;border-radius:4px;padding:2px 5px;font-size:11px}
        .rcp .sub{background:#123b26;color:#fff;border:0;border-radius:22px;padding:13px 32px;font-size:15px;font-weight:700;cursor:pointer}
        .rcp .hint{color:#9a9b91;font-size:12px;margin-top:5px}
    </style>

    <h1>{{ $recipe->exists ? '레시피 수정' : '레시피 작성' }}</h1>
    <form method="POST" action="{{ $recipe->exists ? route('community.recipe.update',$recipe) : route('community.recipe.store') }}" enctype="multipart/form-data">
        @csrf
        @if($recipe->exists)@method('PUT')@endif
        <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

        <div class="f">
            <label>카테고리 *</label>
            <select name="recipe_category_id" required>
                <option value="">선택</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('recipe_category_id',$recipe->recipe_category_id)==$c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="f">
            <label>제목 *</label>
            <input type="text" name="title" value="{{ old('title',$recipe->title) }}" maxlength="200" required placeholder="예: 애플망고 치즈케이크">
        </div>
        <div class="f">
            <label>요약 (선택)</label>
            <input type="text" name="summary" value="{{ old('summary',$recipe->summary) }}" maxlength="300" placeholder="목록에 표시될 한 줄 소개">
        </div>
        <div class="f">
            <label>내용 *</label>
            <textarea name="body" maxlength="5000" required placeholder="재료와 만드는 방법을 자유롭게 적어주세요.">{{ old('body', $recipe->exists ? trim(strip_tags(str_replace(['<br>','<br/>','<br />'],"\n",$recipe->body))) : '') }}</textarea>
        </div>
        <div class="f">
            <label>유튜브 링크 (선택)</label>
            <input type="text" name="video_url" value="{{ old('video_url',$recipe->video_url) }}" placeholder="https://youtu.be/...">
            <div class="hint">요리 영상이 있으면 링크를 넣어주세요.</div>
        </div>

        @if($recipe->exists && $recipe->images->count())
        <div class="f">
            <label>등록된 사진</label>
            <div class="thumbs">
                @foreach($recipe->images as $img)
                    <label><img src="{{ $img->url }}"><span class="rm"><input type="checkbox" name="remove_images[]" value="{{ $img->id }}"> 삭제</span></label>
                @endforeach
            </div>
        </div>
        @endif

        <div class="f">
            <label>사진 첨부 (여러 장)</label>
            <input type="file" name="photos[]" accept="image/*" multiple>
            <div class="hint">첫 사진이 대표 이미지로 사용됩니다. (장당 최대 8MB)</div>
        </div>

        <button class="sub">{{ $recipe->exists ? '수정 저장' : '레시피 등록' }}</button>
        <a href="{{ $recipe->exists ? $recipe->url : route('community.recipes') }}" style="margin-left:10px;color:#77786f;text-decoration:none">취소</a>

        @if($recipe->exists)
        <div style="margin-top:20px;border-top:1px solid #eee;padding-top:16px">
            <span onclick="if(confirm('레시피를 삭제할까요?')){document.getElementById('delForm').submit();}" style="color:#e0322d;font-size:13px;cursor:pointer">이 레시피 삭제</span>
        </div>
        @endif
    </form>
    @if($recipe->exists)
    <form id="delForm" method="POST" action="{{ route('community.recipe.destroy',$recipe) }}" style="display:none">@csrf @method('DELETE')</form>
    @endif
</div>
@endsection
