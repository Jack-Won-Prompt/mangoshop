@extends('layouts.app')
@section('title', '레시피 물어보기 — 망고샵')
@section('desc', '과일 손질법·보관법·레시피가 궁금하다면 물어보세요. 회원과 망고샵이 함께 답해드립니다.')

@section('content')
<div class="rcp container" style="max-width:820px;padding:24px 20px 44px">
    <style>
        .rcp .qhead{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8px;flex-wrap:wrap;gap:10px}
        .rcp .qhead h1{font-size:24px;margin:0}
        .rcp .qhead p{color:#77786f;font-size:13.5px;margin:6px 0 0}
        .rcp .qbtn{background:#123b26;color:#fff;text-decoration:none;padding:11px 20px;border-radius:22px;font-size:14px;font-weight:700}
        .rcp .qtabs{margin:16px 0 8px;font-size:13px}
        .rcp .qtabs a{color:#77786f;text-decoration:none}
        .rcp .qlist{list-style:none;margin:14px 0 0;padding:0}
        .rcp .qlist li{border-top:1px solid #eee;padding:15px 4px}
        .rcp .qlist a.t{font-size:15px;color:#222;text-decoration:none;font-weight:600}
        .rcp .qlist a.t:hover{color:#123b26}
        .rcp .qlist .m{font-size:12px;color:#9a9b91;margin-top:6px;display:flex;gap:10px;flex-wrap:wrap}
        .rcp .qlist .ac{color:#ed7254;font-weight:700}
        .rcp .qcat{display:inline-block;font-size:11px;color:#123b26;background:#eef3ee;border-radius:12px;padding:2px 9px;margin-right:6px}
    </style>

    <div class="qhead">
        <div>
            <h1>🍳 레시피 물어보기</h1>
            <p>과일 손질·보관·레시피가 궁금하면 질문을 남겨보세요. 회원과 망고샵이 함께 답해드립니다.</p>
        </div>
        <a class="qbtn" href="{{ route('community.recipe.qna.ask') }}">질문하기</a>
    </div>

    <div class="qtabs"><a href="{{ route('community.recipes') }}">← 레시피 커뮤니티</a></div>

    <ul class="qlist">
        @forelse($questions as $q)
            <li>
                @if($q->category)<span class="qcat">{{ $q->category->name }}</span>@endif
                <a class="t" href="{{ route('community.recipe.qna.show', $q) }}">{{ $q->title }}</a>
                <div class="m">
                    <span>{{ $q->user->name ?? '회원' }}</span>
                    <span>{{ $q->created_at->format('Y.m.d') }}</span>
                    <span>조회 {{ number_format($q->view_count) }}</span>
                    <span class="ac">답변 {{ $q->answer_count }}</span>
                </div>
            </li>
        @empty
            <li style="text-align:center;color:#9a9b91;padding:40px 0">첫 질문을 남겨보세요!</li>
        @endforelse
    </ul>

    <div style="margin-top:20px">{{ $questions->links() }}</div>
</div>
@endsection
