@php($thumb = $r->cover_url ?: ($r->youtube_id ? 'https://i.ytimg.com/vi/'.$r->youtube_id.'/hqdefault.jpg' : asset('images/fruit/mango-fruit-2.jpg')))
<a class="rcard" href="{{ $r->url }}">
    <div class="thumb" style="background-image:url('{{ $thumb }}')">
        @if($r->is_pinned || $r->is_official)<span class="pin">추천</span>@endif
        @if($r->youtube_id || $r->video_path)<span class="play">▶</span>@endif
    </div>
    <div class="b">
        <div class="cat">{{ $r->category->name ?? '레시피' }}</div>
        <h3>{{ \Illuminate\Support\Str::limit($r->title, 40) }}</h3>
        <div class="meta">조회 {{ number_format($r->view_count) }} · {{ optional($r->published_at)->format('Y.m.d') }}</div>
    </div>
</a>
