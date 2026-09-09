@extends('layouts.app')
@section('title', ($recipe->meta_title ?: $recipe->title).' — 망고샵 레시피')
@section('desc', $recipe->meta_description ?: ($recipe->summary ?: \Illuminate\Support\Str::limit(strip_tags($recipe->body), 150)))

@php($ogImage = $recipe->cover_url ?: ($recipe->youtube_id ? 'https://i.ytimg.com/vi/'.$recipe->youtube_id.'/hqdefault.jpg' : null))
@php($ingredients = collect(preg_split('/\r?\n/', (string) $recipe->ingredients))->map(fn($s)=>trim($s))->filter()->values())

@push('head')
<link rel="canonical" href="{{ $recipe->url }}">
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $recipe->title }}">
<meta property="og:description" content="{{ $recipe->summary ?: \Illuminate\Support\Str::limit(strip_tags($recipe->body), 150) }}">
<meta property="og:url" content="{{ $recipe->url }}">
@if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
<meta name="twitter:card" content="summary_large_image">
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Recipe',
    'name' => $recipe->title,
    'description' => $recipe->summary ?: \Illuminate\Support\Str::limit(strip_tags($recipe->body), 150),
    'image' => $ogImage ? [$ogImage] : null,
    'datePublished' => optional($recipe->published_at)->toDateString(),
    'author' => ['@type' => $recipe->user_id ? 'Person' : 'Organization', 'name' => $recipe->user->name ?? '망고샵'],
    'recipeCategory' => $recipe->category->name ?? null,
    'totalTime' => $recipe->cook_time ?: null,
    'recipeIngredient' => $ingredients->all() ?: null,
    'video' => $recipe->youtube_id ? [
        '@type' => 'VideoObject',
        'name' => $recipe->title,
        'description' => $recipe->summary ?: $recipe->title,
        'thumbnailUrl' => 'https://i.ytimg.com/vi/'.$recipe->youtube_id.'/hqdefault.jpg',
        'contentUrl' => 'https://www.youtube.com/watch?v='.$recipe->youtube_id,
        'embedUrl' => 'https://www.youtube.com/embed/'.$recipe->youtube_id,
        'uploadDate' => optional($recipe->published_at)->toDateString(),
    ] : null,
], fn($v) => !is_null($v)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[
        ['@type'=>'ListItem','position'=>1,'name'=>'레시피','item'=>route('community.recipes')],
        ['@type'=>'ListItem','position'=>2,'name'=>$recipe->category->name ?? '레시피','item'=>route('community.recipe.category',$recipe->category->slug ?? '')],
        ['@type'=>'ListItem','position'=>3,'name'=>$recipe->title,'item'=>$recipe->url],
    ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="rcp container" style="max-width:820px;padding:24px 20px 50px">
    <style>
        .rcp .crumb{font-size:12.5px;color:#9a9b91;margin-bottom:12px}
        .rcp .crumb a{color:#9a9b91;text-decoration:none}
        .rcp h1{font-size:26px;line-height:1.35;margin:0 0 10px}
        .rcp .rmeta{display:flex;gap:12px;align-items:center;color:#9a9b91;font-size:13px;border-bottom:1px solid #eee;padding-bottom:16px;margin-bottom:20px}
        .rcp .rmeta .cat{color:#ed7254;font-weight:700}
        .rcp .video{position:relative;aspect-ratio:16/9;border-radius:12px;overflow:hidden;background:#000;margin:0 0 22px}
        .rcp .video iframe,.rcp .video video{position:absolute;inset:0;width:100%;height:100%;border:0}
        .rcp .cover{width:100%;border-radius:12px;margin:0 0 22px}
        .rcp .ing{background:#fff7e6;border:1px solid #ffe0b2;border-radius:12px;padding:16px 18px;margin:0 0 22px}
        .rcp .ing h3{margin:0 0 10px;font-size:15px}
        .rcp .ing ul{margin:0;padding-left:18px;line-height:1.9;font-size:14px}
        .rcp .body{font-size:15px;line-height:1.85;color:#2b2f2a}
        .rcp .body img{max-width:100%;height:auto;border-radius:8px;margin:10px 0}
        .rcp .gallery{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:22px 0}
        .rcp .gallery img{width:100%;border-radius:8px}
        .rcp .related{margin-top:36px}
        .rcp .related h3{font-size:17px;margin:0 0 14px}
        .rcp .rgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
        .rcp .rcard{border:1px solid #ececE5;border-radius:12px;overflow:hidden;background:#fff;text-decoration:none;color:inherit;display:block}
        .rcp .rcard .thumb{aspect-ratio:1.3;background:#f4f3ec center/cover no-repeat}
        .rcp .rcard .b{padding:10px 11px}
        .rcp .rcard h3{font-size:13px;margin:0;line-height:1.4}
        @media(max-width:640px){.rcp .rgrid{grid-template-columns:repeat(2,1fr)}.rcp .gallery{grid-template-columns:repeat(2,1fr)}}
    </style>

    <div class="crumb"><a href="{{ route('community.recipes') }}">레시피</a> › <a href="{{ route('community.recipe.category',$recipe->category->slug) }}">{{ $recipe->category->name }}</a></div>
    <h1>{{ $recipe->title }}</h1>
    <div class="rmeta">
        <span class="cat">{{ $recipe->category->name }}</span>
        <span>{{ $recipe->user_id ? ($recipe->user->name ?? '회원') : '망고샵' }}</span>
        <span>조회 {{ number_format($recipe->view_count) }}</span>
        <span>{{ optional($recipe->published_at)->format('Y.m.d') }}</span>
        @if($recipe->cook_time)<span>⏱ {{ $recipe->cook_time }}</span>@endif
        @auth
            @if($recipe->user_id && (auth()->id() === $recipe->user_id || auth()->user()->is_admin))
                <a href="{{ route('community.recipe.edit', $recipe) }}" style="color:#123b26;text-decoration:none;font-weight:700">수정</a>
            @elseif($recipe->user_id)
                <form method="POST" action="{{ route('community.recipe.report', $recipe) }}" style="display:inline" onsubmit="return confirm('이 레시피를 신고할까요?')">@csrf
                    <button style="border:0;background:none;color:#c0392b;cursor:pointer;font-size:13px">🚨 신고</button>
                </form>
            @endif
        @endauth
    </div>

    {{-- 영상: 유튜브 우선, 없으면 업로드 영상 --}}
    @if($recipe->youtube_id)
        <div class="video"><iframe src="https://www.youtube.com/embed/{{ $recipe->youtube_id }}" title="{{ $recipe->title }}" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen loading="lazy"></iframe></div>
    @elseif($recipe->video_file_url)
        <div class="video"><video src="{{ $recipe->video_file_url }}" controls preload="metadata" @if($recipe->cover_url)poster="{{ $recipe->cover_url }}"@endif></video></div>
    @elseif($recipe->cover_url)
        <img class="cover" src="{{ $recipe->cover_url }}" alt="{{ $recipe->title }}">
    @endif

    @if($ingredients->count())
        <div class="ing"><h3>🧺 재료</h3><ul>@foreach($ingredients as $ing)<li>{{ $ing }}</li>@endforeach</ul></div>
    @endif

    <div class="body">{!! \App\Support\Media::html($recipe->body) ?: '<p>내용이 없습니다.</p>' !!}</div>

    @if($recipe->images->count())
        <div class="gallery">
            @foreach($recipe->images as $img)<img src="{{ $img->url }}" alt="{{ $recipe->title }} 사진 {{ $loop->iteration }}" loading="lazy">@endforeach
        </div>
    @endif

    @if($related->count())
    <div class="related">
        <h3>같은 카테고리 레시피</h3>
        <div class="rgrid">
            @foreach($related as $r)
                @php($thumb = $r->cover_url ?: ($r->youtube_id ? 'https://i.ytimg.com/vi/'.$r->youtube_id.'/hqdefault.jpg' : asset('images/fruit/mango-fruit-2.jpg')))
                <a class="rcard" href="{{ $r->url }}"><div class="thumb" style="background-image:url('{{ $thumb }}')"></div><div class="b"><h3>{{ \Illuminate\Support\Str::limit($r->title,30) }}</h3></div></a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
