@extends('layouts.app')
@section('title', $question->title.' — 레시피 물어보기 · 망고샵')
@section('desc', \Illuminate\Support\Str::limit($question->body, 150))

@section('content')
<div class="rcp container" style="max-width:760px;padding:24px 20px 44px">
    <style>
        .rcp .crumb{font-size:12.5px;color:#9a9b91;margin-bottom:12px}
        .rcp .crumb a{color:#9a9b91;text-decoration:none}
        .rcp .qcat{display:inline-block;font-size:11px;color:#123b26;background:#eef3ee;border-radius:12px;padding:2px 9px;margin-bottom:8px}
        .rcp h1{font-size:22px;line-height:1.4;margin:0 0 10px}
        .rcp .qmeta{font-size:12.5px;color:#9a9b91;display:flex;gap:12px;border-bottom:1px solid #eee;padding-bottom:14px;margin-bottom:18px}
        .rcp .qbody{font-size:15px;line-height:1.85;white-space:pre-wrap;color:#2b2f2a;margin-bottom:30px}
        .rcp .acount{font-size:16px;font-weight:700;margin:0 0 14px}
        .rcp .ans{border:1px solid #eee;border-radius:12px;padding:16px 18px;margin-bottom:12px;background:#fff}
        .rcp .ans.admin{background:#f2f8f4;border-color:#cfe6d6}
        .rcp .ans .who{font-size:13px;font-weight:700;color:#33415c;margin-bottom:6px;display:flex;align-items:center;gap:6px}
        .rcp .ans .who .badge{background:#123b26;color:#fff;font-size:10px;padding:2px 7px;border-radius:10px}
        .rcp .ans .txt{font-size:14px;line-height:1.8;white-space:pre-wrap;color:#2b2f2a}
        .rcp .ans .date{font-size:11px;color:#aaa;margin-top:8px}
        .rcp .aform{margin-top:22px;border-top:1px solid #eee;padding-top:20px}
        .rcp .aform textarea{width:100%;min-height:120px;border:1px solid #dcdcd4;border-radius:8px;padding:11px 12px;font-size:14px;font-family:inherit;line-height:1.7;resize:vertical}
        .rcp .aform .sub{margin-top:10px;background:#123b26;color:#fff;border:0;border-radius:22px;padding:11px 26px;font-weight:700;cursor:pointer}
        .rcp .login-note{margin-top:20px;padding:16px;background:#f7f6f0;border-radius:10px;text-align:center;font-size:14px}
        .rcp .login-note a{color:#123b26;font-weight:700}
    </style>

    <div class="crumb"><a href="{{ route('community.recipes') }}">레시피</a> › <a href="{{ route('community.recipe.qna') }}">물어보기</a></div>

    @if($question->category)<span class="qcat">{{ $question->category->name }}</span>@endif
    <h1>{{ $question->title }}</h1>
    <div class="qmeta">
        <span>{{ $question->user->name ?? '회원' }}</span>
        <span>{{ $question->created_at->format('Y.m.d H:i') }}</span>
        <span>조회 {{ number_format($question->view_count) }}</span>
    </div>
    <div class="qbody">{{ $question->body }}</div>

    @if($question->youtube_id)
        <div style="position:relative;aspect-ratio:16/9;border-radius:12px;overflow:hidden;background:#000;margin:0 0 18px">
            <iframe src="https://www.youtube.com/embed/{{ $question->youtube_id }}" title="{{ $question->title }}" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;border:0"></iframe>
        </div>
    @endif
    @if($question->images->count())
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:0 0 22px">
            @foreach($question->images as $img)<img src="{{ $img->url }}" alt="{{ $question->title }} 사진 {{ $loop->iteration }}" loading="lazy" style="width:100%;border-radius:8px">@endforeach
        </div>
    @endif

    <div class="acount">답변 {{ $question->answers->count() }}</div>
    @forelse($question->answers as $a)
        <div class="ans {{ $a->is_admin ? 'admin' : '' }}">
            <div class="who">
                {{ $a->is_admin ? '망고샵' : ($a->user->name ?? '회원') }}
                @if($a->is_admin)<span class="badge">관리자</span>@endif
            </div>
            <div class="txt">{{ $a->body }}</div>
            <div class="date">{{ $a->created_at->format('Y.m.d H:i') }}
                @auth @if(auth()->user()->is_admin)
                    <form method="POST" action="{{ route('admin.recipe-answers.destroy', $a) }}" style="display:inline;margin-left:8px" onsubmit="return confirm('답변을 삭제할까요?')">@csrf @method('DELETE')
                        <button style="border:0;background:none;color:#e0322d;font-size:11px;cursor:pointer">삭제</button>
                    </form>
                @endif @endauth
            </div>
        </div>
    @empty
        <p style="color:#9a9b91;padding:8px 0 16px">등록된 답변이 없습니다.</p>
    @endforelse

    @auth
        <form class="aform" method="POST" action="{{ route('community.recipe.qna.answer', $question) }}">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <label style="font-weight:700;font-size:14px;display:block;margin-bottom:8px">
                답변 남기기 @if(auth()->user()->is_admin)<span style="color:#123b26">(관리자로 등록됩니다)</span>@endif
            </label>
            <textarea name="body" maxlength="3000" required placeholder="도움이 되는 답변을 남겨주세요."></textarea>
            <button class="sub">답변 등록</button>
        </form>
    @else
        <div class="login-note">답변을 남기려면 <a href="{{ route('login') }}">로그인</a>이 필요합니다.</div>
    @endauth
</div>
@endsection
