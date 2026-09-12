@extends('layouts.app')
@section('title', ($category->meta_title ?: $category->name.' 레시피').' — 망고샵')
@section('desc', $category->meta_description ?: ($category->tagline ?: $category->name.' 관련 레시피와 요리 후기 모음 — 망고샵 레시피 커뮤니티'))

@push('head')
<link rel="canonical" href="{{ route('community.recipe.category', $category->slug) }}">
<meta property="og:title" content="{{ $category->name }} 레시피 — 망고샵">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('community.recipe.category', $category->slug) }}">
@if($category->cover_url)<meta property="og:image" content="{{ $category->cover_url }}">@endif
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>'레시피','item'=>route('community.recipes')],
        ['@type'=>'ListItem','position'=>2,'name'=>$category->name,'item'=>route('community.recipe.category',$category->slug)],
    ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="rcp container" style="padding:24px 20px 40px">
    <style>
        .rcp .crumb{font-size:12.5px;color:#9a9b91;margin-bottom:10px}
        .rcp .crumb a{color:#9a9b91;text-decoration:none}
        .rcp .chead h1{font-size:24px;margin:0 0 6px}
        .rcp .chead p{color:#77786f;font-size:14px;margin:0 0 6px}
        .rcp .cnav{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0 24px}
        .rcp .cnav a{padding:7px 14px;border:1px solid #e6e5de;border-radius:20px;text-decoration:none;color:#2b2f2a;font-size:13px;background:#fff}
        .rcp .cnav a.on{background:#123b26;color:#fff;border-color:#123b26}
        .rcp h2.sec{font-size:17px;margin:22px 0 13px;display:flex;align-items:center;gap:8px}
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
        .rcp .toolbar{display:flex;justify-content:flex-end;gap:6px;margin-bottom:12px}
        .rcp .toolbar a{font-size:12.5px;color:#77786f;text-decoration:none;padding:4px 8px;border-radius:6px}
        .rcp .toolbar a.on{background:#f0efe8;color:#123b26;font-weight:700}
        @media(max-width:900px){.rcp .rgrid{grid-template-columns:repeat(2,1fr)}}
    </style>

    <div class="crumb"><a href="{{ route('community.recipes') }}">레시피</a> › {{ $category->name }}</div>
    <div class="chead">
        <h1>{{ $category->name }} 레시피</h1>
        @if($category->tagline)<p>{{ $category->tagline }}</p>@endif
        @if($category->description)<p>{{ $category->description }}</p>@endif
    </div>

    <div class="cnav">
        <a href="{{ route('community.recipes') }}">전체</a>
        @foreach($categories as $c)
            <a href="{{ route('community.recipe.category',$c->slug) }}" class="{{ $c->id===$category->id?'on':'' }}">{{ $c->name }}</a>
        @endforeach
    </div>

    @if($pinned->count())
        <h2 class="sec">📌 추천 레시피</h2>
        <div class="rgrid">
            @foreach($pinned as $r)@include('community.recipes._card', ['r' => $r])@endforeach
        </div>
    @endif

    <h2 class="sec">회원 레시피 · 요리 이야기
        <a href="{{ route('community.recipe.create') }}" style="margin-left:auto;font-size:12.5px;background:#123b26;color:#fff;text-decoration:none;padding:7px 14px;border-radius:18px;font-weight:700">✍️ 레시피 작성</a>
    </h2>
    <div class="toolbar">
        <a href="{{ route('community.recipe.category',[$category->slug]) }}" class="{{ $sort==='latest'?'on':'' }}">최신순</a>
        <a href="{{ route('community.recipe.category',[$category->slug,'sort'=>'popular']) }}" class="{{ $sort==='popular'?'on':'' }}">인기순</a>
    </div>
    @if($posts->count())
        <div class="rgrid">
            @foreach($posts as $r)@include('community.recipes._card', ['r' => $r])@endforeach
        </div>
        <div style="margin-top:20px">{{ $posts->links() }}</div>
    @else
        <p style="color:#9a9b91;padding:20px 0">등록된 회원 레시피가 없습니다.</p>
    @endif
</div>
@endsection
