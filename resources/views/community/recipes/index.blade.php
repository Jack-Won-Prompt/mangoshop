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
        <a href="{{ route('community.recipe.qna') }}" style="background:#fff;border:1px solid #123b26;color:#123b26;text-decoration:none;padding:10px 18px;border-radius:22px;font-size:13.5px;font-weight:700;white-space:nowrap">💬 레시피 물어보기</a>
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
@endsection
