@extends('layouts.app')
@section('title', '레시피 커뮤니티 — 망고샵')
@section('desc', '망고·아보카도·열대과일로 만드는 레시피 모음. 애플망고 스무디, 망고 빙수 등 신선한 과일 레시피와 회원들의 요리 후기를 만나보세요.')

@section('content')
<div class="rcp container" style="padding:24px 20px 40px">
    <style>
        .rcp .rcp-head{margin-bottom:18px}
        .rcp .rcp-head h1{font-size:26px;margin:0 0 6px}
        .rcp .rcp-head p{color:#77786f;font-size:14px;margin:0}
        .rcp .rcp-cats{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0 26px}
        .rcp .rcp-cats a{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border:1px solid #e6e5de;border-radius:22px;text-decoration:none;color:#2b2f2a;font-size:13.5px;background:#fff}
        .rcp .rcp-cats a:hover{border-color:#123b26;color:#123b26}
        .rcp .rcp-cats a b{color:#ed7254;font-size:12px}
        .rcp h2.sec{font-size:18px;margin:26px 0 14px;display:flex;align-items:center;gap:8px}
        .rcp .rgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
        .rcp .rcard{border:1px solid #ececE5;border-radius:12px;overflow:hidden;background:#fff;text-decoration:none;color:inherit;display:block;transition:.15s}
        .rcp .rcard:hover{transform:translateY(-3px);box-shadow:0 8px 22px #153b2414}
        .rcp .rcard .thumb{aspect-ratio:1.3;background:#f4f3ec center/cover no-repeat;position:relative}
        .rcp .rcard .pin{position:absolute;top:8px;left:8px;background:#123b26;color:#fff;font-size:11px;padding:3px 8px;border-radius:20px}
        .rcp .rcard .play{position:absolute;right:8px;bottom:8px;background:#000a;color:#fff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:13px}
        .rcp .rcard .b{padding:12px 13px 14px}
        .rcp .rcard .cat{font-size:11px;color:#ed7254;font-weight:700}
        .rcp .rcard h3{font-size:14px;margin:5px 0 4px;line-height:1.4}
        .rcp .rcard .meta{font-size:11px;color:#9a9b91}
        @media(max-width:900px){.rcp .rgrid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:520px){.rcp .rgrid{grid-template-columns:repeat(2,1fr);gap:10px}}
    </style>

    <div class="rcp-head" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:10px">
        <div>
            <h1>🍳 레시피 커뮤니티</h1>
            <p>신선한 수입 과일로 만드는 레시피와 회원들의 요리 이야기</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @auth
                <button type="button" class="rcp-openbtn ghost" onclick="rcpShow('askForm')">💬 레시피 물어보기</button>
                <button type="button" class="rcp-openbtn" onclick="rcpShow('writeForm')">✍️ 레시피 작성</button>
            @else
                <a href="{{ route('login') }}" class="rcp-openbtn ghost" style="text-decoration:none">💬 레시피 물어보기</a>
                <a href="{{ route('login') }}" class="rcp-openbtn" style="text-decoration:none">✍️ 레시피 작성</a>
            @endauth
            <a href="{{ route('community.recipe.qna') }}" style="align-self:center;font-size:12.5px;color:#77786f;text-decoration:none">전체 Q&A →</a>
        </div>
    </div>

    @if($categories->count())
    <div class="rcp-cats">
        @foreach($categories as $c)
            <a href="{{ route('community.recipe.category', $c->slug) }}">{{ $c->name }} <b>{{ $c->recipes_count }}</b></a>
        @endforeach
    </div>
    @endif

    @php($cardsPin = $pinned)
    @if($cardsPin->count())
        <h2 class="sec">📌 추천 레시피</h2>
        <div class="rgrid">
            @foreach($cardsPin as $r)
                @include('community.recipes._card', ['r' => $r])
            @endforeach
        </div>
    @endif

    @if($latest->count())
        <h2 class="sec">🆕 최신 레시피</h2>
        <div class="rgrid">
            @foreach($latest as $r)
                @include('community.recipes._card', ['r' => $r])
            @endforeach
        </div>
    @else
        <p style="color:#9a9b91;padding:30px 0">아직 등록된 레시피가 없습니다.</p>
    @endif
</div>

@auth
{{-- ===== 리스트 하단 인라인 작성 영역 ===== --}}
<div class="rcp container" style="max-width:820px;padding:0 20px 44px">
    {{-- 레시피 작성 --}}
    <section id="writeForm" class="rcp-writebox" hidden>
        <h2>✍️ 레시피 작성</h2>
        <form method="POST" action="{{ route('community.recipe.store') }}" enctype="multipart/form-data" class="rcp-form">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <label>카테고리 *<select name="recipe_category_id" required><option value="">선택</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label>
            <label>제목 *<input type="text" name="title" maxlength="200" required placeholder="예: 애플망고 치즈케이크"></label>
            <label>요약(선택)<input type="text" name="summary" maxlength="300" placeholder="한 줄 소개"></label>
            <label>내용 *<textarea name="body" maxlength="5000" required placeholder="재료와 만드는 방법을 적어주세요."></textarea></label>
            <label>유튜브 링크(선택)<input type="text" name="video_url" placeholder="https://youtu.be/..."></label>
            <label>태그(선택)<input type="text" name="tags" placeholder="쉼표 구분 · 예: 애플망고, 디저트"></label>
            <label>사진(여러 장)<input type="file" name="photos[]" accept="image/*" multiple></label>
            <label>영상 파일(선택·mp4/webm)<input type="file" name="video" accept="video/mp4,video/webm,video/quicktime"></label>
            <div class="btns"><button class="submit">레시피 등록</button><button type="button" class="cancel" onclick="rcpHide('writeForm')">접기</button></div>
        </form>
    </section>

    {{-- 레시피 물어보기 --}}
    <section id="askForm" class="rcp-writebox" hidden>
        <h2>💬 레시피 물어보기</h2>
        <form method="POST" action="{{ route('community.recipe.qna.store') }}" enctype="multipart/form-data" class="rcp-form">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <label>카테고리(선택)<select name="recipe_category_id"><option value="">일반</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label>
            <label>제목 *<input type="text" name="title" maxlength="150" required placeholder="예: 애플망고 어떻게 보관하나요?"></label>
            <label>내용 *<textarea name="body" maxlength="3000" required placeholder="궁금한 점을 자세히 적어주세요."></textarea></label>
            <label>사진 첨부(선택·여러 장)<input type="file" name="photos[]" accept="image/*" multiple></label>
            <label>유튜브 링크(선택)<input type="text" name="video_url" placeholder="https://youtu.be/..."></label>
            <div class="btns"><button class="submit">질문 등록</button><button type="button" class="cancel" onclick="rcpHide('askForm')">접기</button></div>
        </form>
    </section>
</div>
@endauth

<style>
    .rcp-openbtn{border:0;background:#123b26;color:#fff;padding:10px 18px;border-radius:22px;font-size:13.5px;font-weight:700;white-space:nowrap;cursor:pointer}
    .rcp-openbtn.ghost{background:#fff;border:1px solid #123b26;color:#123b26}
    .rcp-writebox{border:1px solid #e6e5de;border-radius:14px;padding:20px 22px 24px;margin-top:18px;background:#fcfcf9}
    .rcp-writebox h2{font-size:17px;margin:0 0 14px}
    .rcp-form{display:grid;gap:13px}
    .rcp-form label{display:block;font-size:13px;font-weight:600;color:#33415c}
    .rcp-form input,.rcp-form select,.rcp-form textarea{width:100%;border:1px solid #dcdcd4;border-radius:8px;padding:10px 12px;font-size:14px;font-family:inherit;margin-top:6px}
    .rcp-form textarea{min-height:120px;line-height:1.6;resize:vertical}
    .rcp-form .btns{display:flex;gap:8px;align-items:center;margin-top:4px}
    .rcp-form .submit{background:#123b26;color:#fff;border:0;border-radius:22px;padding:11px 26px;font-size:15px;font-weight:700;cursor:pointer}
    .rcp-form .cancel{background:none;border:0;color:#77786f;cursor:pointer;font-size:13px}
</style>
<script>
    function rcpShow(id){
        ['writeForm','askForm'].forEach(function(x){var e=document.getElementById(x);if(e)e.hidden=(x!==id);});
        var el=document.getElementById(id); if(!el)return;
        el.hidden=false; el.scrollIntoView({behavior:'smooth',block:'start'});
        var f=el.querySelector('input[name=title]'); if(f) setTimeout(function(){try{f.focus();}catch(e){}},350);
    }
    function rcpHide(id){var e=document.getElementById(id);if(e)e.hidden=true;}
</script>
@endsection
