@extends('layouts.app')

@section('content')

{{-- ===== 1. 풀와이드 롤링 히어로 (화이트 · 실사진 · 생동감) ===== --}}
@if($mainBanners->count())
@php($heroAcc = [asset('images/fruit/lychee-fruit-0.jpg'), asset('images/fruit/mangosteen-0.jpg'), asset('images/fruit/grapefruit-0.jpg')])
<section class="mg-hero" aria-label="메인 배너">
    <div class="mg-hero-track" id="mgHero">
        @foreach($mainBanners as $i => $b)
            <div class="mg-slide {{ $i === 0 ? 'on' : '' }}">
                <div class="mg-wrap">
                    <div class="mg-slide-txt">
                        <small>MANGOSHOP</small>
                        <h2>{{ $b->title }}</h2>
                        @if($b->subtitle)<p>{{ $b->subtitle }}</p>@endif
                        <a href="{{ $b->link ?: route('catalog.index') }}" class="mg-slide-btn">상품 보러가기 <x-icon name="arrow-right" :size="16"/></a>
                    </div>
                    <div class="mg-slide-visual" aria-hidden="true">
                        <span class="mg-visual-ring"></span>
                        <div class="mg-visual-main" style="background-image:url('{{ $b->image_url ?: $heroAcc[0] }}')"></div>
                        <span class="mg-visual-acc a1" style="background-image:url('{{ $b->accUrl(1) ?: $heroAcc[0] }}')"></span>
                        <span class="mg-visual-acc a2" style="background-image:url('{{ $b->accUrl(2) ?: $heroAcc[1] }}')"></span>
                        <span class="mg-visual-acc a3" style="background-image:url('{{ $b->accUrl(3) ?: $heroAcc[2] }}')"></span>
                    </div>
                </div>
            </div>
        @endforeach
        @if($mainBanners->count() > 1)
            <button class="mg-hero-arrow prev" id="mgHeroPrev" aria-label="이전">‹</button>
            <button class="mg-hero-arrow next" id="mgHeroNext" aria-label="다음">›</button>
            <div class="mg-hero-dots" id="mgHeroDots">
                @foreach($mainBanners as $i => $b)<button class="{{ $i===0?'on':'' }}" data-i="{{ $i }}" aria-label="배너 {{ $i+1 }}"></button>@endforeach
            </div>
        @endif
    </div>
</section>
@endif

{{-- ===== 2. 안내 스트립 (관리자 사이트설정에서 편집) ===== --}}
@php($strip = collect($site['home_strip'] ?? [])->filter(fn ($s) => !empty($s['title']) || !empty($s['desc'])))
@if($strip->count())
<div class="mg-strip">
    <div class="mg-wrap">
        @foreach($strip as $s)
            <div class="item"><span class="ic"><x-icon :name="$s['icon'] ?: 'check'" :size="21"/></span><b>{{ $s['title'] ?? '' }}</b> {{ $s['desc'] ?? '' }}</div>
        @endforeach
    </div>
</div>
@endif

{{-- ===== 3. 메인 상품 섹션 (관리자 사이트설정에서 순서/노출 관리) ===== --}}
@php($homeSections = collect($site['home_sections'] ?? [['key'=>'best','visible'=>true],['key'=>'new','visible'=>true],['key'=>'deal','visible'=>true]])->filter(fn($s)=>($s['visible'] ?? true)))
@foreach($homeSections as $sec)
@switch($sec['key'])
@case('new')
@if($newProducts->count())
<section class="mg-section">
    <div class="mg-wrap">
        <div class="mg-sec-head">
            <div class="ki">NEW ARRIVAL</div>
            <h3>{{ $site['home_new_title'] ?? '새로 들어온 과일' }}</h3>
            <p>{{ $site['home_new_sub'] ?? '이번 주 새롭게 입고된 신상품' }}</p>
        </div>
        <div class="mg-grid g5">
            @foreach($newProducts->take(5) as $p)<x-product-card :product="$p"/>@endforeach
        </div>
    </div>
</section>
@endif
@break
@case('best')
@if($bestProducts->count())
<section class="mg-section">
    <div class="mg-wrap">
        <div class="mg-sec-head">
            <div class="ki">BEST ITEM</div>
            <h3>이달의 베스트 과일</h3>
            <p>망고샵에서 가장 많이 찾는 인기 수입 과일</p>
        </div>
        <div class="mg-grid g5">
            @foreach($bestProducts->take(10) as $p)<x-product-card :product="$p"/>@endforeach
        </div>
        <div class="mg-sec-more"><a href="{{ route('catalog.index', ['sort' => 'best']) }}" class="mg-btn gho" style="padding:12px 28px">베스트 전체보기 <x-icon name="arrow-right" :size="15"/></a></div>
    </div>
</section>
@endif
@break
@case('deal')
@if($featuredProducts->count())
<section class="mg-section alt">
    <div class="mg-wrap">
        <div class="mg-sec-head biz">
            <div class="ki" style="color:var(--mg-accent-d)">FOR BUSINESS</div>
            <h3>사업자 전용 도매 특가</h3>
            <p>도매회원 승인 후 도매 전용가와 수량구간 대량할인으로 만나보세요</p>
        </div>
        <div class="mg-grid g5">
            @foreach($dealProducts->take(5) as $p)<x-product-card :product="$p"/>@endforeach
        </div>
        <div class="mg-sec-more"><a href="{{ route('catalog.index', ['grade' => 'wholesale']) }}" class="mg-btn pri" style="padding:12px 28px">사업자 전용몰 <x-icon name="arrow-right" :size="15"/></a></div>
    </div>
</section>
@endif
@break
@endswitch
@endforeach

{{-- ===== 3-1. 최신 등록 레시피 (선물셋트 아래) · 관리자 설정으로 노출 토글 ===== --}}
@if(($recipeItems ?? collect())->count() && ($site['home_show_recipe'] ?? true))
<section class="mg-section">
    <div class="mg-wrap">
        <div class="mg-sec-head">
            <div class="ki">RECIPE</div>
            <h3>최신 등록 레시피</h3>
            <p>신선한 과일로 만드는 레시피와 회원들의 요리 이야기</p>
        </div>
        <div class="mg-recipe-grid">
            @foreach($recipeItems as $r)
                @php($thumb = $r->cover_url ?: ($r->youtube_id ? 'https://i.ytimg.com/vi/'.$r->youtube_id.'/hqdefault.jpg' : asset('images/fruit/mango-fruit-2.jpg')))
                <a class="mg-recipe-card" href="{{ $r->url }}">
                    <span class="th" style="background-image:url('{{ $thumb }}')">@if($r->youtube_id || $r->video_path)<i>▶</i>@endif</span>
                    <span class="cat">{{ $r->category->name ?? '레시피' }}</span>
                    <b>{{ \Illuminate\Support\Str::limit($r->title, 32) }}</b>
                </a>
            @endforeach
        </div>
        <div class="mg-sec-more"><a href="{{ route('community.recipes') }}" class="mg-btn gho" style="padding:12px 28px">레시피 더보기 <x-icon name="arrow-right" :size="15"/></a></div>
    </div>
</section>
<style>
.mg-recipe-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.mg-recipe-card{display:block;text-decoration:none;color:inherit;border:1px solid #ececE5;border-radius:12px;overflow:hidden;background:#fff;transition:.15s}
.mg-recipe-card:hover{transform:translateY(-3px);box-shadow:0 8px 22px #153b2414}
.mg-recipe-card .th{display:block;aspect-ratio:1.3;background:#f4f3ec center/cover no-repeat;position:relative}
.mg-recipe-card .th i{position:absolute;right:8px;bottom:8px;background:#000a;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-style:normal;font-size:12px}
.mg-recipe-card .cat{display:block;font-size:11px;color:#ed7254;font-weight:700;padding:11px 12px 0}
.mg-recipe-card b{display:block;font-size:14px;padding:4px 12px 14px;line-height:1.4}
@media(max-width:900px){.mg-recipe-grid{grid-template-columns:repeat(2,1fr)}}
</style>
@endif

{{-- ===== 4-1. 실사진 피처 밴드 (적립·캐시백 / 수입사 직거래·입점) · 관리자 사이트설정에서 편집 ===== --}}
@php($feats = $site['home_features'] ?? [])
@php($featUrl = fn($v) => $v ? (preg_match('#^https?://#i', $v) ? $v : asset(ltrim($v, '/'))) : '')
@php($featOverlay = ['linear-gradient(120deg, rgba(255,183,71,.12), rgba(255,107,0,.05))', 'linear-gradient(120deg, rgba(22,163,74,.10), rgba(22,163,74,.04))'])
@if(count($feats))
<section class="mg-feature">
    <div class="mg-wrap">
        @foreach($feats as $i => $f)
            @php($img = $featUrl($f['image'] ?? ''))
            @php($isBiz = $i % 2 === 1)
            <div class="mg-feat-row {{ $isBiz ? 'b rev' : 'a' }}">
                <div class="mg-feat-photo" role="img" aria-label="{{ strip_tags($f['kicker'] ?? '') }}"
                     @if($img)style="background-image:{{ ($featOverlay[$i] ?? $featOverlay[0]) }}, url('{{ $img }}')"@endif></div>
                <div class="mg-feat-txt">
                    @if(!empty($f['kicker']))<div class="ki">{{ $f['kicker'] }}</div>@endif
                    @if(!empty($f['title']))<h3>{!! \App\Support\Media::headline($f['title']) !!}</h3>@endif
                    @if(!empty($f['desc']))<p>{{ $f['desc'] }}</p>@endif
                    @if(!empty($f['chips']))
                        <div class="mg-feat-chips">
                            @foreach((array) $f['chips'] as $chip)<span>{{ $chip }}</span>@endforeach
                        </div>
                    @endif
                    @if(!empty($f['button']) && !empty($f['link']))
                        @php($lnk = preg_match('#^https?://#i', $f['link']) ? $f['link'] : url($f['link']))
                        <a href="{{ $lnk }}" class="mg-btn {{ $isBiz ? 'gho' : 'pri' }}" style="padding:12px 26px">{{ $f['button'] }} <x-icon name="arrow-right" :size="15"/></a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- ===== 6. 입점 수입사 섹션은 요청에 따라 숨김 ===== --}}

{{-- ===== 7. 인스타그램 피드 ===== --}}
<section class="mg-insta">
    <div class="mg-wrap">
        <div class="mg-sec-head">
            <div class="ki">INSTAGRAM</div>
            <h3>@mangoshop</h3>
            <p>망고샵의 신선한 순간을 인스타그램에서 만나보세요</p>
        </div>
        <div class="mg-insta-grid">
            @php($photos = ['mango-nam-dok-mai-0.jpg','avocado-fruit-0.jpg','orange-fruit-0.jpg','pineapple-fruit-0.jpg','durian-fruit-0.jpg','mango-fruit-2.jpg','grapefruit-0.jpg','mangosteen-0.jpg'])
            @foreach($photos as $ph)
                <a href="{{ route('catalog.index') }}" aria-label="망고샵 인스타그램">
                    <img src="{{ asset('images/fruit/'.$ph) }}" alt="망고샵 인스타그램" loading="lazy">
                    <span class="ov"><x-icon name="instagram" :size="26"/></span>
                </a>
            @endforeach
        </div>
        <div class="mg-insta-follow">
            <a href="https://www.instagram.com/" target="_blank" rel="noopener" class="mg-btn gho" style="padding:11px 26px">FOLLOW <x-icon name="arrow-right" :size="15"/></a>
        </div>
    </div>
</section>

{{-- 메인 팝업(관리자 사이트설정 > 메인 팝업) --}}
@include('partials.home-popup')

@endsection

@push('scripts')
<script>
(function () {
    var track = document.getElementById('mgHero');
    if (!track) return;
    var slides = track.querySelectorAll('.mg-slide');
    var dots = track.querySelectorAll('#mgHeroDots button');
    if (slides.length < 2) return;
    var cur = 0, timer;
    function go(n) {
        cur = (n + slides.length) % slides.length;
        slides.forEach(function (s, i) { s.classList.toggle('on', i === cur); });
        dots.forEach(function (d, i) { d.classList.toggle('on', i === cur); });
    }
    function next() { go(cur + 1); }
    function start() { timer = setInterval(next, 5000); }
    function reset() { clearInterval(timer); start(); }
    dots.forEach(function (d) { d.addEventListener('click', function () { go(+d.dataset.i); reset(); }); });
    var pv = document.getElementById('mgHeroPrev'), nx = document.getElementById('mgHeroNext');
    if (pv) pv.addEventListener('click', function () { go(cur - 1); reset(); });
    if (nx) nx.addEventListener('click', function () { go(cur + 1); reset(); });
    start();
})();
</script>
@endpush
