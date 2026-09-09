@extends('layouts.app')
@section('title', '#'.$tag.' 레시피 — 망고샵')
@section('desc', '#'.$tag.' 태그가 달린 레시피 모음 — 망고샵 레시피 커뮤니티')

@push('head')
<link rel="canonical" href="{{ route('community.recipe.tag', $tag) }}">
@endpush

@section('content')
<div class="rcp container" style="padding:24px 20px 40px">
    <style>
        .rcp .crumb{font-size:12.5px;color:#9a9b91;margin-bottom:10px}
        .rcp .crumb a{color:#9a9b91;text-decoration:none}
        .rcp h1{font-size:24px;margin:0 0 18px}
        .rcp .rgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
        .rcp .rcard{border:1px solid #ececE5;border-radius:12px;overflow:hidden;background:#fff;text-decoration:none;color:inherit;display:block;transition:.15s}
        .rcp .rcard:hover{transform:translateY(-3px);box-shadow:0 8px 22px #153b2414}
        .rcp .rcard .thumb{aspect-ratio:1.3;background:#f4f3ec center/cover no-repeat;position:relative}
        .rcp .rcard .pin{position:absolute;top:8px;left:8px;background:#123b26;color:#fff;font-size:11px;padding:3px 8px;border-radius:20px}
        .rcp .rcard .play{position:absolute;right:8px;bottom:8px;background:#000a;color:#fff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center}
        .rcp .rcard .b{padding:12px 13px 14px}
        .rcp .rcard .cat{font-size:11px;color:#ed7254;font-weight:700}
        .rcp .rcard h3{font-size:14px;margin:5px 0 4px;line-height:1.4}
        .rcp .rcard .meta{font-size:11px;color:#9a9b91}
        @media(max-width:900px){.rcp .rgrid{grid-template-columns:repeat(2,1fr)}}
    </style>

    <div class="crumb"><a href="{{ route('community.recipes') }}">레시피</a> › 태그</div>
    <h1>#{{ $tag }}</h1>

    @if($recipes->count())
        <div class="rgrid">
            @foreach($recipes as $r)@include('community.recipes._card', ['r' => $r])@endforeach
        </div>
        <div style="margin-top:20px">{{ $recipes->links() }}</div>
    @else
        <p style="color:#9a9b91;padding:30px 0">이 태그의 레시피가 아직 없습니다.</p>
    @endif
</div>
@endsection
