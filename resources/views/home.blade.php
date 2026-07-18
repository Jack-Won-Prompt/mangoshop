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
                        <span class="mg-visual-acc a1" style="background-image:url('{{ $heroAcc[0] }}')"></span>
                        <span class="mg-visual-acc a2" style="background-image:url('{{ $heroAcc[1] }}')"></span>
                        <span class="mg-visual-acc a3" style="background-image:url('{{ $heroAcc[2] }}')"></span>
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

{{-- ===== 2. 안내 스트립 ===== --}}
<div class="mg-strip">
    <div class="mg-wrap">
        <div class="item"><span class="ic"><x-icon name="truck" :size="21"/></span><b>콜드체인 냉장배송</b> 신선하게 산지직송</div>
        <div class="item"><span class="ic"><x-icon name="building" :size="21"/></span><b>검증된 수입사</b> 엄선 입점 수입사</div>
        <div class="item"><span class="ic"><x-icon name="coin" :size="21"/></span><b>수량구간 대량할인</b> 도매회원 구간별 도매가</div>
        <div class="item"><span class="ic"><x-icon name="doc" :size="21"/></span><b>세금계산서</b> 사업자 발행 지원</div>
    </div>
</div>

{{-- ===== 3. 이달의 베스트 ===== --}}
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

{{-- ===== 4. 사업자 전용 (도매) ===== --}}
@if($featuredProducts->count())
<section class="mg-section alt">
    <div class="mg-wrap">
        <div class="mg-sec-head biz">
            <div class="ki" style="color:var(--mg-accent-d)">FOR BUSINESS</div>
            <h3>사업자 전용 도매 특가</h3>
            <p>도매회원 승인 후 도매 전용가와 수량구간 대량할인으로 만나보세요</p>
        </div>
        <div class="mg-grid g5">
            @foreach($featuredProducts->take(5) as $p)<x-product-card :product="$p"/>@endforeach
        </div>
        <div class="mg-sec-more"><a href="{{ route('catalog.index', ['grade' => 'wholesale']) }}" class="mg-btn pri" style="padding:12px 28px">사업자 전용몰 <x-icon name="arrow-right" :size="15"/></a></div>
    </div>
</section>
@endif

{{-- ===== 4-1. 프로모 밴드 (리뷰 적립 / 수입사 입점 모집) ===== --}}
<section class="mg-promo">
    <div class="mg-wrap">
        <div class="mg-promo-card review">
            <span class="em" aria-hidden="true">🎁</span>
            <div class="tag">MEMBERSHIP BENEFIT</div>
            <h4>상품 후기 작성만 해도<br><b>3% 적립금</b> 지급</h4>
            <div class="mg-promo-rows">
                <div class="mg-promo-row"><span class="n">혜택1</span> 신규 회원가입 시 <b>3,000P</b> 즉시 지급</div>
                <div class="mg-promo-row"><span class="n">혜택2</span> 구매금액 3% + 상품후기 3% 적립</div>
            </div>
            <a href="{{ route('community.reviews') }}" class="mg-promo-btn">후기 쓰고 적립받기 <x-icon name="arrow-right" :size="15"/></a>
        </div>
        <div class="mg-promo-card join">
            <span class="em" aria-hidden="true">🏬</span>
            <div class="tag">FOR SELLERS</div>
            <h4>수입사 <b>입점 파트너</b><br>상시 모집합니다</h4>
            <div class="tel">1600-0000</div>
            <p style="font-size:13px;opacity:.92;margin:0 0 16px;line-height:1.6">검증된 수입사라면 누구나 · 수수료·정산·콜드체인 배송까지 원스톱 지원</p>
            <a href="{{ route('community.inquiry') }}" class="mg-promo-btn">입점 문의하기 <x-icon name="arrow-right" :size="15"/></a>
        </div>
    </div>
</section>

{{-- ===== 5. 신상품 ===== --}}
@if($newProducts->count())
<section class="mg-section">
    <div class="mg-wrap">
        <div class="mg-sec-head">
            <div class="ki">NEW ARRIVAL</div>
            <h3>새로 들어온 과일</h3>
            <p>이번 주 새롭게 입고된 신상품</p>
        </div>
        <div class="mg-grid g5">
            @foreach($newProducts->take(5) as $p)<x-product-card :product="$p"/>@endforeach
        </div>
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
