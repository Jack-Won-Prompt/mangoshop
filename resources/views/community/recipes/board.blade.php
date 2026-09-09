@extends('layouts.app')
@section('title', ($category ? $category->name.' 레시피' : '레시피 커뮤니티').' — 망고샵')
@section('desc', $category?->meta_description ?: '망고·아보카도·열대과일 레시피와 요리 질문을 나누는 망고샵 레시피 커뮤니티')

@php
    $rowUrl = function ($r) {
        return $r->kind === 'question'
            ? route('community.recipe.qna.show', $r->id)
            : route('community.recipe.show', [$r->cat_slug ?: 'recipe', $r->recipe_slug]);
    };
    $baseParams = array_filter(['type' => $type, 'sort' => $sort]);
    $total = $rows->total();
    $startNo = $total - (($rows->currentPage() - 1) * $rows->perPage());
@endphp

@section('content')
<div class="dcb container" style="max-width:1000px;padding:20px 16px 44px">
<style>
    .dcb .bhead{display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:12px}
    .dcb .bhead h1{font-size:22px;margin:0}
    .dcb .bhead p{color:#8a8b82;font-size:13px;margin:5px 0 0}
    .dcb .cats{display:flex;flex-wrap:wrap;gap:6px;margin:12px 0}
    .dcb .cats a{font-size:13px;color:#333;text-decoration:none;padding:6px 13px;border:1px solid #e5e4dd;border-radius:6px;background:#fff}
    .dcb .cats a.on{background:#123b26;color:#fff;border-color:#123b26}
    .dcb .toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;border-bottom:2px solid #333;padding-bottom:8px;margin-top:6px}
    .dcb .tabs a{font-size:13px;color:#666;text-decoration:none;padding:4px 10px;border-radius:5px}
    .dcb .tabs a.on{background:#eef2ee;color:#123b26;font-weight:700}
    .dcb .wbtns{display:flex;gap:6px}
    .dcb .wbtns button,.dcb .wbtns a{border:0;border-radius:6px;padding:8px 14px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none}
    .dcb .wbtns .q{background:#fff;border:1px solid #123b26;color:#123b26}
    .dcb .wbtns .w{background:#123b26;color:#fff}
    .dcb table{width:100%;border-collapse:collapse;font-size:13px;margin-top:2px}
    .dcb thead th{background:#f7f7f2;color:#666;font-weight:600;padding:9px 6px;border-bottom:1px solid #e5e4dd;font-size:12px}
    .dcb tbody td{padding:9px 6px;border-bottom:1px solid #f0efe9;text-align:center;color:#555;white-space:nowrap}
    .dcb tbody td.t{text-align:left;white-space:normal}
    .dcb tr.notice{background:#fbfaf3}
    .dcb .mm{display:inline-block;font-size:11px;color:#ed7254;font-weight:700;margin-right:4px}
    .dcb .mm.q{color:#2a72c9}
    .dcb .mm.notice{color:#c9640a}
    .dcb td.t a{color:#222;text-decoration:none;font-weight:500}
    .dcb td.t a:hover{text-decoration:underline}
    .dcb .cmt{color:#ed7254;font-weight:700;font-size:12px;margin-left:4px}
    .dcb .num{color:#aaa;font-size:12px}
    .dcb .pin{display:inline-block;background:#123b26;color:#fff;font-size:10px;border-radius:3px;padding:1px 5px;margin-right:4px}
    .dcb .empty{text-align:center;color:#9a9b91;padding:40px 0}
    @media(max-width:640px){
        .dcb thead .hide,.dcb tbody .hide{display:none}
        .dcb table{font-size:12.5px}
    }
    .dcb-writebox{border:1px solid #e6e5de;border-radius:12px;padding:18px 20px 22px;margin-top:18px;background:#fcfcf9}
    .dcb-writebox h2{font-size:16px;margin:0 0 12px}
    .dcb-form{display:grid;gap:12px}
    .dcb-form label{display:block;font-size:13px;font-weight:600;color:#33415c}
    .dcb-form input,.dcb-form select,.dcb-form textarea{width:100%;border:1px solid #dcdcd4;border-radius:8px;padding:10px 12px;font-size:14px;font-family:inherit;margin-top:6px}
    .dcb-form textarea{min-height:120px;line-height:1.6;resize:vertical}
    .dcb-form .btns{display:flex;gap:8px;align-items:center;margin-top:4px}
    .dcb-form .submit{background:#123b26;color:#fff;border:0;border-radius:22px;padding:11px 26px;font-size:15px;font-weight:700;cursor:pointer}
    .dcb-form .cancel{background:none;border:0;color:#77786f;cursor:pointer;font-size:13px}
</style>

    <div class="bhead">
        <div>
            <h1>🍳 {{ $category ? $category->name.' 레시피' : '레시피 커뮤니티' }}</h1>
            <p>{{ $category->tagline ?? '레시피를 공유하고 궁금한 요리를 물어보세요' }}</p>
        </div>
    </div>

    {{-- 카테고리(갤러리) --}}
    <div class="cats">
        <a href="{{ route('community.recipes', $baseParams) }}" class="{{ $category ? '' : 'on' }}">전체</a>
        @foreach($categories as $c)
            <a href="{{ route('community.recipe.category', array_merge([$c->slug], $baseParams)) }}" class="{{ $category && $category->id===$c->id ? 'on' : '' }}">{{ $c->name }} <span style="color:#bbb">{{ $c->recipes_count }}</span></a>
        @endforeach
    </div>

    {{-- 말머리 + 정렬 + 글쓰기 --}}
    @php($catRoute = fn($extra) => $category ? route('community.recipe.category', array_merge([$category->slug], $extra)) : route('community.recipes', $extra))
    <div class="toolbar">
        <div class="tabs">
            <a href="{{ $catRoute(['sort'=>$sort]) }}" class="{{ !$type?'on':'' }}">전체</a>
            <a href="{{ $catRoute(['type'=>'recipe','sort'=>$sort]) }}" class="{{ $type==='recipe'?'on':'' }}">레시피</a>
            <a href="{{ $catRoute(['type'=>'question','sort'=>$sort]) }}" class="{{ $type==='question'?'on':'' }}">질문</a>
            <span style="color:#ddd">|</span>
            <a href="{{ $catRoute(array_filter(['type'=>$type])) }}" class="{{ $sort==='latest'?'on':'' }}">최신</a>
            <a href="{{ $catRoute(array_filter(['type'=>$type,'sort'=>'popular'])) }}" class="{{ $sort==='popular'?'on':'' }}">인기</a>
            <a href="{{ $catRoute(array_filter(['type'=>$type,'sort'=>'best'])) }}" class="{{ $sort==='best'?'on':'' }}">개념글</a>
        </div>
        <div class="wbtns">
            @auth
                <button type="button" class="q" onclick="dcbShow('askForm')">💬 물어보기</button>
                <button type="button" class="w" onclick="dcbShow('writeForm')">✍️ 글쓰기</button>
            @else
                <a class="q" href="{{ route('login') }}">💬 물어보기</a>
                <a class="w" href="{{ route('login') }}">✍️ 글쓰기</a>
            @endauth
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:54px">번호</th>
                <th class="t" style="text-align:left">제목</th>
                <th style="width:100px" class="hide">글쓴이</th>
                <th style="width:64px" class="hide">작성일</th>
                <th style="width:48px">조회</th>
                <th style="width:48px">추천</th>
            </tr>
        </thead>
        <tbody>
            {{-- 고정글 --}}
            @foreach($notices as $r)
                <tr class="notice">
                    <td><span class="num">공지</span></td>
                    <td class="t">
                        <span class="mm notice">[{{ $r->is_official ? '공식' : '고정' }}]</span>
                        <a href="{{ $rowUrl($r) }}">{{ $r->title }}</a>
                        @if($r->cmt)<span class="cmt">[{{ $r->cmt }}]</span>@endif
                    </td>
                    <td class="hide">{{ $r->author }}</td>
                    <td class="hide">{{ \Illuminate\Support\Carbon::parse($r->created)->format('m.d') }}</td>
                    <td>{{ number_format($r->view_count) }}</td>
                    <td>{{ number_format($r->rec) }}</td>
                </tr>
            @endforeach

            {{-- 일반글 --}}
            @forelse($rows as $i => $r)
                <tr>
                    <td><span class="num">{{ $startNo - $i }}</span></td>
                    <td class="t">
                        <span class="mm {{ $r->kind==='question'?'q':'' }}">[{{ $r->kind==='question' ? '질문' : '레시피' }}]</span>
                        @if(!$category && $r->cat_name)<span style="color:#aaa;font-size:11px">{{ $r->cat_name }}·</span>@endif
                        <a href="{{ $rowUrl($r) }}">{{ $r->title }}</a>
                        @if($r->cmt)<span class="cmt">[{{ $r->cmt }}]</span>@endif
                    </td>
                    <td class="hide">{{ $r->author }}</td>
                    <td class="hide">{{ \Illuminate\Support\Carbon::parse($r->created)->format('m.d') }}</td>
                    <td>{{ number_format($r->view_count) }}</td>
                    <td>{{ number_format($r->rec) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">게시글이 없습니다. 첫 글을 남겨보세요!</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:18px">{{ $rows->links() }}</div>

    @auth
    {{-- ===== 하단 인라인 작성 ===== --}}
    <section id="writeForm" class="dcb-writebox" hidden>
        <h2>✍️ 레시피 작성</h2>
        <form method="POST" action="{{ route('community.recipe.store') }}" enctype="multipart/form-data" class="dcb-form">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <label>카테고리 *<select name="recipe_category_id" required><option value="">선택</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected($category && $category->id===$c->id)>{{ $c->name }}</option>@endforeach</select></label>
            <label>제목 *<input type="text" name="title" maxlength="200" required placeholder="예: 애플망고 치즈케이크"></label>
            <label>요약(선택)<input type="text" name="summary" maxlength="300" placeholder="한 줄 소개"></label>
            <label>내용 *<textarea name="body" maxlength="5000" required placeholder="재료와 만드는 방법을 적어주세요."></textarea></label>
            <label>유튜브 링크(선택)<input type="text" name="video_url" placeholder="https://youtu.be/..."></label>
            <label>태그(선택)<input type="text" name="tags" placeholder="쉼표 구분 · 예: 애플망고, 디저트"></label>
            <label>사진(여러 장)<input type="file" name="photos[]" accept="image/*" multiple></label>
            <label>영상 파일(선택·mp4/webm)<input type="file" name="video" accept="video/mp4,video/webm,video/quicktime"></label>
            <div class="btns"><button class="submit">레시피 등록</button><button type="button" class="cancel" onclick="dcbHide('writeForm')">접기</button></div>
        </form>
    </section>
    <section id="askForm" class="dcb-writebox" hidden>
        <h2>💬 레시피 물어보기</h2>
        <form method="POST" action="{{ route('community.recipe.qna.store') }}" enctype="multipart/form-data" class="dcb-form">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <label>카테고리(선택)<select name="recipe_category_id"><option value="">일반</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected($category && $category->id===$c->id)>{{ $c->name }}</option>@endforeach</select></label>
            <label>제목 *<input type="text" name="title" maxlength="150" required placeholder="예: 애플망고 어떻게 보관하나요?"></label>
            <label>내용 *<textarea name="body" maxlength="3000" required placeholder="궁금한 점을 자세히 적어주세요."></textarea></label>
            <label>사진 첨부(선택·여러 장)<input type="file" name="photos[]" accept="image/*" multiple></label>
            <label>유튜브 링크(선택)<input type="text" name="video_url" placeholder="https://youtu.be/..."></label>
            <div class="btns"><button class="submit">질문 등록</button><button type="button" class="cancel" onclick="dcbHide('askForm')">접기</button></div>
        </form>
    </section>
    @endauth
</div>

<script>
    function dcbShow(id){
        ['writeForm','askForm'].forEach(function(x){var e=document.getElementById(x);if(e)e.hidden=(x!==id);});
        var el=document.getElementById(id); if(!el)return;
        el.hidden=false; el.scrollIntoView({behavior:'smooth',block:'center'});
        var f=el.querySelector('input[name=title]'); if(f) setTimeout(function(){try{f.focus();}catch(e){}},350);
    }
    function dcbHide(id){var e=document.getElementById(id);if(e)e.hidden=true;}
</script>
@endsection
