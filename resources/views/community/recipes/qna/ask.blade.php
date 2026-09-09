@extends('layouts.app')
@section('title', '레시피 질문하기 — 망고샵')

@section('content')
<div class="rcp container" style="max-width:680px;padding:24px 20px 44px">
    <style>
        .rcp h1{font-size:22px;margin:0 0 18px}
        .rcp form label{display:block;font-size:13.5px;font-weight:600;color:#33415c;margin-bottom:6px}
        .rcp form .f{margin-bottom:18px}
        .rcp form input,.rcp form select,.rcp form textarea{width:100%;border:1px solid #dcdcd4;border-radius:8px;padding:11px 12px;font-size:14px;font-family:inherit}
        .rcp form textarea{min-height:180px;line-height:1.7;resize:vertical}
        .rcp .sub{background:#123b26;color:#fff;border:0;border-radius:22px;padding:13px 30px;font-size:15px;font-weight:700;cursor:pointer}
    </style>

    <h1>레시피 질문하기</h1>
    <form method="POST" action="{{ route('community.recipe.qna.store') }}">
        @csrf
        <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

        <div class="f">
            <label>카테고리 (선택)</label>
            <select name="recipe_category_id">
                <option value="">일반</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('recipe_category_id')==$c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="f">
            <label>제목 *</label>
            <input type="text" name="title" value="{{ old('title') }}" maxlength="150" required placeholder="예: 애플망고 어떻게 보관하나요?">
        </div>
        <div class="f">
            <label>내용 *</label>
            <textarea name="body" maxlength="3000" required placeholder="궁금한 점을 자세히 적어주세요.">{{ old('body') }}</textarea>
        </div>
        <button class="sub">질문 등록</button>
        <a href="{{ route('community.recipe.qna') }}" style="margin-left:10px;color:#77786f;text-decoration:none">취소</a>
    </form>
</div>
@endsection
