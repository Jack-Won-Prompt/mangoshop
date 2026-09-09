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
                <button type="button" class="rcp-openbtn ghost" onclick="rcpOpen('askModal')">💬 레시피 물어보기</button>
                <button type="button" class="rcp-openbtn" onclick="rcpOpen('writeModal')">✍️ 레시피 작성</button>
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
{{-- ===== 레시피 작성 모달 ===== --}}
<div class="rcp-modal" id="writeModal" aria-hidden="true">
    <div class="rcp-dim" onclick="rcpClose('writeModal')"></div>
    <div class="rcp-dialog" role="dialog" aria-label="레시피 작성">
        <div class="rcp-mhead"><b>✍️ 레시피 작성</b><button type="button" class="x" onclick="rcpClose('writeModal')">×</button></div>
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
            <button class="submit">레시피 등록</button>
        </form>
    </div>
</div>

{{-- ===== 레시피 물어보기 모달 ===== --}}
<div class="rcp-modal" id="askModal" aria-hidden="true">
    <div class="rcp-dim" onclick="rcpClose('askModal')"></div>
    <div class="rcp-dialog" role="dialog" aria-label="레시피 물어보기">
        <div class="rcp-mhead"><b>💬 레시피 물어보기</b><button type="button" class="x" onclick="rcpClose('askModal')">×</button></div>
        <form method="POST" action="{{ route('community.recipe.qna.store') }}" enctype="multipart/form-data" class="rcp-form">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <label>카테고리(선택)<select name="recipe_category_id"><option value="">일반</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label>
            <label>제목 *<input type="text" name="title" maxlength="150" required placeholder="예: 애플망고 어떻게 보관하나요?"></label>
            <label>내용 *<textarea name="body" maxlength="3000" required placeholder="궁금한 점을 자세히 적어주세요."></textarea></label>
            <label>사진 첨부(선택·여러 장)<input type="file" name="photos[]" accept="image/*" multiple></label>
            <label>유튜브 링크(선택)<input type="text" name="video_url" placeholder="https://youtu.be/..."></label>
            <button class="submit">질문 등록</button>
        </form>
    </div>
</div>
@endauth

<style>
    .rcp-openbtn{border:0;background:#123b26;color:#fff;padding:10px 18px;border-radius:22px;font-size:13.5px;font-weight:700;white-space:nowrap;cursor:pointer}
    .rcp-openbtn.ghost{background:#fff;border:1px solid #123b26;color:#123b26}
    .rcp-modal{position:fixed;inset:0;z-index:1000;display:none}
    .rcp-modal.on{display:block}
    .rcp-dim{position:absolute;inset:0;background:rgba(20,30,20,.5)}
    .rcp-dialog{position:relative;max-width:560px;margin:5vh auto;background:#fff;border-radius:14px;max-height:90vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
    .rcp-mhead{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #eee;position:sticky;top:0;background:#fff}
    .rcp-mhead b{font-size:16px}
    .rcp-mhead .x{border:0;background:none;font-size:24px;cursor:pointer;color:#888;line-height:1}
    .rcp-form{padding:18px 20px 24px;display:grid;gap:14px}
    .rcp-form label{display:block;font-size:13px;font-weight:600;color:#33415c}
    .rcp-form input,.rcp-form select,.rcp-form textarea{width:100%;border:1px solid #dcdcd4;border-radius:8px;padding:10px 12px;font-size:14px;font-family:inherit;margin-top:6px}
    .rcp-form textarea{min-height:130px;line-height:1.6;resize:vertical}
    .rcp-form .submit{background:#123b26;color:#fff;border:0;border-radius:22px;padding:12px;font-size:15px;font-weight:700;cursor:pointer;margin-top:4px}
</style>
<script>
    function rcpOpen(id){var m=document.getElementById(id);if(m){m.classList.add('on');document.body.style.overflow='hidden';}}
    function rcpClose(id){var m=document.getElementById(id);if(m){m.classList.remove('on');document.body.style.overflow='';}}
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){document.querySelectorAll('.rcp-modal.on').forEach(function(m){m.classList.remove('on');});document.body.style.overflow='';}});
</script>
@endsection
