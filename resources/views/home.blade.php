@extends('layouts.app')

@section('content')

{{-- ===== 메인페이지(목업 디자인 이식) — .mgx 로 스코프 ===== --}}
<style>
.mgx{--g:#123b26;--ink:#18241c;--muted:#74766f;--cream:#f8f5ec;--yellow:#ffc928;--coral:#ed7254;--line:#e8e7e0;color:var(--ink)}
.mgx .wrap{max-width:1200px;margin:0 auto;padding:0 24px}
.mgx .eyebrow{color:#788178;font-size:10px;letter-spacing:1.2px;font-weight:700}
.mgx .btn{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:22px;padding:12px 23px;background:var(--g);color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:13px}
.mgx .btn.yellow{background:var(--yellow);color:var(--ink)}
.mgx .btn.ghost{background:#fff;color:var(--g);border:1px solid var(--g)}
/* hero */
.mgx .hero{height:360px;position:relative;overflow:hidden;background:#f6ecdc}
.mgx .hero .slide{position:absolute;inset:0;opacity:0;transition:opacity .7s}
.mgx .hero .slide.on{opacity:1}
.mgx .hero .slide img{width:100%;height:100%;object-fit:cover;object-position:center;position:absolute;inset:0}
.mgx .hero .slide::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#fffefbcc 0 38%,#fffefb55 55%,transparent 72%)}
.mgx .hero .copy{position:absolute;z-index:2;top:50%;transform:translateY(-50%);left:0;width:100%}
.mgx .hero .copy .wrap{max-width:1200px}
.mgx .hero .copy small{font-family:Georgia,'Noto Serif KR',serif;font-size:16px;color:var(--ink)}
.mgx .hero .copy h1{font-family:Georgia,'Noto Serif KR',serif;color:var(--g);font-size:42px;margin:14px 0 8px;letter-spacing:-2px;line-height:1.1}
.mgx .hero .copy p{font-size:18px;color:var(--coral);margin:0 0 20px}
.mgx .hero .dots{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);z-index:3;display:flex;gap:7px}
.mgx .hero .dots button{width:8px;height:8px;border-radius:50%;border:0;background:#0002;cursor:pointer;padding:0}
.mgx .hero .dots button.on{background:var(--g)}
.mgx .hero .arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:3;border:0;background:#fff9;width:40px;height:40px;border-radius:50%;font-size:22px;color:var(--g);cursor:pointer}
.mgx .hero .arrow.prev{left:14px}.mgx .hero .arrow.next{right:14px}
/* benefit */
.mgx .benefit{position:relative;z-index:4;margin:-32px auto 26px;max-width:1152px;width:calc(100% - 48px);background:#fff;border-radius:13px;box-shadow:0 4px 16px #0b381613;display:grid;grid-template-columns:repeat(4,1fr)}
.mgx .benefit>div{display:flex;align-items:center;justify-content:center;gap:10px;border-right:1px solid var(--line);padding:16px 10px}
.mgx .benefit>div:last-child{border:0}
.mgx .benefit img{width:30px;height:34px;object-fit:contain}
.mgx .benefit b{display:block;font-size:13px}
.mgx .benefit small{display:block;color:var(--muted);font-size:10px;margin-top:3px}
/* section heading + grid */
.mgx .section{margin:30px 0}
.mgx .heading{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:16px}
.mgx .heading h2{font-family:Georgia,'Noto Serif KR',serif;font-size:23px;margin:6px 0 0}
.mgx .heading .sub{color:var(--muted);font-size:12px;margin:6px 0 0}
.mgx .heading a{font-size:12px;color:var(--muted);text-decoration:none;white-space:nowrap}
.mgx .pgrid{display:grid;gap:14px;grid-template-columns:repeat(5,1fr)}
/* business banner */
.mgx .biz{position:relative;overflow:hidden;border-radius:12px;background:var(--g);color:#fff;display:flex;align-items:center;justify-content:center;gap:44px;padding:26px;text-align:center}
.mgx .biz img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.16}
.mgx .biz>div{position:relative;z-index:1}
.mgx .biz h2{font-family:Georgia,'Noto Serif KR',serif;font-size:26px;margin:5px 0 6px}
.mgx .biz p{font-size:12px;margin:0;opacity:.92}
.mgx .biz .btn{position:relative;z-index:1}
/* two-col reward + partner */
.mgx .two{display:grid;grid-template-columns:1fr 1.25fr;gap:14px}
.mgx .card{border-radius:12px;padding:30px;position:relative;overflow:hidden;min-height:230px}
.mgx .card.reward{background:#fff7e6}
.mgx .card.partner{background:#fbfaf3}
.mgx .card h2{font-family:Georgia,'Noto Serif KR',serif;font-size:24px;line-height:1.3;margin:9px 0}
.mgx .card h2 strong{color:var(--coral)}
.mgx .card p{font-size:12px;line-height:1.7;color:#555}
.mgx .reward-items{display:flex;gap:8px;margin:16px 0}
.mgx .reward-items div{background:#fff;border-radius:8px;padding:10px 14px;text-align:center;font-size:13px;font-weight:700;color:var(--g)}
.mgx .card.partner img{position:absolute;width:56%;right:-6%;bottom:0;opacity:.95;mix-blend-mode:multiply}
.mgx .card.partner .btn{position:relative;z-index:1}
/* instagram */
.mgx .insta{display:grid;grid-template-columns:210px 1fr;gap:18px;align-items:center;margin:30px 0}
.mgx .insta h2{font-size:22px;margin:6px 0 8px}
.mgx .insta p{font-size:12px;line-height:1.6;color:var(--muted)}
.mgx .insta-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}
.mgx .insta-grid a{position:relative;display:block;border-radius:8px;overflow:hidden;aspect-ratio:1}
.mgx .insta-grid img{width:100%;height:100%;object-fit:cover;display:block}
@media(max-width:900px){
  .mgx .hero{height:320px}
  .mgx .hero .copy h1{font-size:32px}
  .mgx .benefit{grid-template-columns:repeat(2,1fr)}
  .mgx .benefit>div:nth-child(2){border-right:0}
  .mgx .pgrid{grid-template-columns:repeat(3,1fr)}
  .mgx .two{grid-template-columns:1fr}
  .mgx .insta{grid-template-columns:1fr}
  .mgx .insta-grid{grid-template-columns:repeat(6,1fr)}
}
@media(max-width:620px){
  .mgx .wrap{padding:0 15px}
  .mgx .hero{height:300px}
  .mgx .hero .slide::after{background:linear-gradient(180deg,#fffefbdd 0 45%,#fffefb44 70%,transparent)}
  .mgx .hero .copy h1{font-size:26px}
  .mgx .hero .copy p{font-size:14px}
  .mgx .hero .arrow{display:none}
  .mgx .pgrid{grid-template-columns:repeat(2,1fr);gap:10px}
  .mgx .biz{flex-direction:column;gap:14px}
  .mgx .insta-grid{grid-template-columns:repeat(3,1fr)}
}
</style>

@php
    $heroSlides = [];
    if ($mainBanners->count()) {
        foreach ($mainBanners as $i => $b) {
            $heroSlides[] = [
                'img'   => $b->image_url ?: asset('images/main/hero-0'.(($i % 3) + 1).'.jpg'),
                'small' => 'MANGOSHOP',
                'title' => $b->title,
                'sub'   => $b->subtitle,
                'link'  => $b->link ?: route('catalog.index'),
            ];
        }
    } else {
        $heroSlides = [
            ['img' => asset('images/main/hero-01.jpg'), 'small' => '산지에서 바로 온, 오늘의 가장 달콤한 과일', 'title' => '태국 애플망고 산지직송', 'sub' => '남독마이 특품 5kg 최대 10% 도매 특가', 'link' => route('catalog.index')],
            ['img' => asset('images/main/hero-02.jpg'), 'small' => '검증된 수입사 직거래', 'title' => '콜드체인으로 신선하게', 'sub' => '산지의 맛을 그대로, 망고샵', 'link' => route('catalog.index')],
            ['img' => asset('images/main/hero-03.jpg'), 'small' => '사업자 전용 도매 특가', 'title' => '수량구간 대량할인', 'sub' => '도매회원 승인 후 전용가로 만나보세요', 'link' => route('catalog.index', ['grade' => 'wholesale'])],
        ];
    }
    $heroJs = [];
    foreach ($heroSlides as $s) {
        $heroJs[] = ['small' => $s['small'], 'title' => $s['title'], 'sub' => $s['sub'], 'link' => $s['link']];
    }
@endphp

<div class="mgx">

    {{-- ===== 히어로 슬라이더 ===== --}}
    <section class="hero" id="mgxHero" aria-label="메인 프로모션">
        @foreach($heroSlides as $i => $s)
            <div class="slide {{ $i === 0 ? 'on' : '' }}">
                <img src="{{ $s['img'] }}" alt="{{ $s['title'] }}" @if($i>0) loading="lazy" @endif>
            </div>
        @endforeach
        <div class="copy">
            <div class="wrap">
                <div class="copy-inner" id="mgxHeroCopy">
                    <small>{{ $heroSlides[0]['small'] }}</small>
                    <h1>{{ $heroSlides[0]['title'] }}</h1>
                    @if($heroSlides[0]['sub'])<p>{{ $heroSlides[0]['sub'] }}</p>@endif
                    <a href="{{ $heroSlides[0]['link'] }}" class="btn" id="mgxHeroBtn">상품 보러가기 <span>›</span></a>
                </div>
            </div>
        </div>
        @if(count($heroSlides) > 1)
            <button class="arrow prev" id="mgxPrev" aria-label="이전">‹</button>
            <button class="arrow next" id="mgxNext" aria-label="다음">›</button>
            <div class="dots" id="mgxDots">
                @foreach($heroSlides as $i => $s)<button class="{{ $i===0?'on':'' }}" data-i="{{ $i }}" aria-label="배너 {{ $i+1 }}"></button>@endforeach
            </div>
        @endif
    </section>

    {{-- ===== 혜택 4아이콘 ===== --}}
    <section class="benefit">
        <div><img src="{{ asset('images/main/benefit-01.png') }}" alt=""><span><b>당일 발송 · 콜드체인</b><small>신선함을 그대로 빠르게</small></span></div>
        <div><img src="{{ asset('images/main/benefit-02.png') }}" alt=""><span><b>검증된 수입사 상품</b><small>믿을 수 있는 품질</small></span></div>
        <div><img src="{{ asset('images/main/benefit-03.png') }}" alt=""><span><b>수량구간 대량할인</b><small>구매할수록 더 큰 혜택</small></span></div>
        <div><img src="{{ asset('images/main/benefit-04.png') }}" alt=""><span><b>세금계산서</b><small>사업자 세금계산서 발행</small></span></div>
    </section>

    {{-- ===== NEW ARRIVAL ===== --}}
    @if($newProducts->count())
    <section class="section wrap" id="new">
        <div class="heading">
            <div><span class="eyebrow">NEW ARRIVAL</span><h2>{{ $site['home_new_title'] ?? '이번 주 새롭게 입고된 신상품' }}</h2></div>
            <a href="{{ route('catalog.index', ['sort' => 'new']) }}">전체 보기 ›</a>
        </div>
        <div class="pgrid">
            @foreach($newProducts->take(5) as $p)<x-product-card :product="$p"/>@endforeach
        </div>
    </section>
    @endif

    {{-- ===== BEST ITEM ===== --}}
    @if($bestProducts->count())
    <section class="section wrap" id="best">
        <div class="heading">
            <div><span class="eyebrow">BEST ITEM</span><h2>이달의 베스트 과일</h2></div>
            <a href="{{ route('catalog.index', ['sort' => 'best']) }}">전체 보기 ›</a>
        </div>
        <div class="pgrid">
            @foreach($bestProducts->take(10) as $p)<x-product-card :product="$p"/>@endforeach
        </div>
    </section>
    @endif

    {{-- ===== 사업자 전용 도매 배너 ===== --}}
    <section class="section wrap" id="business">
        <div class="biz">
            <img src="{{ asset('images/main/business.png') }}" alt="사업자 전용 도매 특가">
            <div><span class="eyebrow" style="color:#ffe9a8">FOR BUSINESS</span><h2>사업자 전용 도매 특가</h2><p>도매회원 승인 후 도매 전용가와 수량구간 대량할인으로 만나보세요.</p></div>
            <a href="{{ route('catalog.index', ['grade' => 'wholesale']) }}" class="btn yellow">사업자 전용몰 ›</a>
        </div>
    </section>

    {{-- ===== 적립 + 수입사 직거래 ===== --}}
    <section class="section wrap" id="gift">
        <div class="two">
            <article class="card reward">
                <span class="eyebrow">POINTS &amp; CASHBACK</span>
                <h2>사고, 후기 쓰면<br><strong>적립금</strong>이 쌓입니다</h2>
                <p>구매부터 후기까지, 망고샵을 이용할수록 혜택이 커집니다.</p>
                <div class="reward-items"><div>신규가입<br>3,000P</div><div>구매<br>3% 적립</div><div>후기<br>3% 적립</div></div>
                <a href="{{ route('community.reviews') }}" class="btn yellow">후기 쓰고 적립받기 ›</a>
            </article>
            <article class="card partner">
                <span class="eyebrow">IMPORT &amp; DISTRIBUTION</span>
                <h2>검증된 수입사와<br><strong>직거래</strong> 합니다</h2>
                <p>글로벌 파트너십으로 들여온<br>프리미엄 열대과일을 산지 그대로.</p>
                <a href="{{ route('community.inquiry') }}" class="btn">입점 문의하기 ›</a>
                <img src="{{ asset('images/main/partners.png') }}" alt="검증된 수입사와 직거래">
            </article>
        </div>
    </section>

    {{-- ===== 인스타그램 ===== --}}
    <section class="section wrap insta">
        <div class="insta-copy">
            <span class="eyebrow">INSTAGRAM</span>
            <h2>@mangoshop</h2>
            <p>망고샵의 신선한 순간을<br>인스타그램에서 만나보세요</p>
            <a href="https://www.instagram.com/" target="_blank" rel="noopener" class="btn">FOLLOW ›</a>
        </div>
        <div class="insta-grid">
            @for($i=1;$i<=6;$i++)
                <a href="https://www.instagram.com/" target="_blank" rel="noopener"><img src="{{ asset('images/main/instagram-0'.$i.'.png') }}" alt="망고샵 인스타그램" loading="lazy"></a>
            @endfor
        </div>
    </section>

</div>

@endsection

@push('scripts')
<script>
(function () {
    var hero = document.getElementById('mgxHero');
    if (!hero) return;
    var slides = hero.querySelectorAll('.slide');
    var dots = hero.querySelectorAll('#mgxDots button');
    var slideData = @json($heroJs);
    var copyBox = document.getElementById('mgxHeroCopy');
    if (slides.length < 2) return;
    var cur = 0, timer;
    function render(n) {
        var d = slideData[n];
        copyBox.querySelector('small').textContent = d.small || '';
        copyBox.querySelector('h1').textContent = d.title || '';
        var p = copyBox.querySelector('p'); if (p) p.textContent = d.sub || '';
        var btn = document.getElementById('mgxHeroBtn'); if (btn && d.link) btn.setAttribute('href', d.link);
    }
    function go(n) {
        cur = (n + slides.length) % slides.length;
        slides.forEach(function (s, i) { s.classList.toggle('on', i === cur); });
        dots.forEach(function (x, i) { x.classList.toggle('on', i === cur); });
        render(cur);
    }
    function next() { go(cur + 1); }
    function start() { timer = setInterval(next, 5000); }
    function reset() { clearInterval(timer); start(); }
    dots.forEach(function (x) { x.addEventListener('click', function () { go(+x.dataset.i); reset(); }); });
    var pv = document.getElementById('mgxPrev'), nx = document.getElementById('mgxNext');
    if (pv) pv.addEventListener('click', function () { go(cur - 1); reset(); });
    if (nx) nx.addEventListener('click', function () { go(cur + 1); reset(); });
    start();
})();
</script>
@endpush
