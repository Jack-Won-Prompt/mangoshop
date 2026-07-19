{{-- ===== 상단 유틸 바 ===== --}}
<div class="mg-topbar">
    <div class="mg-wrap">
        <ul class="mg-tb-nav mg-tb-left">
            <li><a href="{{ route('community.notices') }}">공지사항</a></li>
            <li><a href="{{ route('community.qna') }}">Q&amp;A</a></li>
            <li><a href="{{ route('community.reviews') }}">상품후기</a></li>
            <li><a href="{{ route('community.notices') }}">이벤트</a></li>
        </ul>
        <div class="mg-tb-right">
            @auth
                <span>{{ auth()->user()->name }}님</span>
                <span class="sep">|</span>
                @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.dashboard') }}">관리자</a><span class="sep">|</span>
                @endif
                @if(auth()->user()->seller)
                    <a href="{{ url('/seller') }}">판매자센터</a><span class="sep">|</span>
                @endif
                @if(auth()->user()->isAgent())
                    <a href="{{ route('agent.index') }}">대행자센터</a><span class="sep">|</span>
                @endif
                <a href="{{ route('mypage.index') }}">마이쇼핑</a>
                <span class="sep">|</span>
                <a href="{{ route('mypage.orders') }}">주문내역</a>
                <span class="sep">|</span>
                <a href="{{ route('cart.index') }}">장바구니@if(($cartCount ?? 0) > 0) ({{ $cartCount }})@endif</a>
                <span class="sep">|</span>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf
                    <button type="submit" style="background:none;border:0;color:inherit;cursor:pointer;font:inherit;padding:0">로그아웃</button>
                </form>
            @else
                <a href="{{ route('login') }}">로그인</a>
                <span class="sep">|</span>
                <a href="{{ route('register') }}">회원가입</a>
                <span class="sep">|</span>
                <a href="{{ route('cart.index') }}">장바구니</a>
                <span class="sep">|</span>
                <a href="{{ route('mypage.orders') }}">주문내역</a>
                <a href="{{ route('register') }}" class="mg-tb-point">가입시 {{ number_format($site['signup_point'] ?? 3000) }}P</a>
            @endauth
        </div>
    </div>
</div>

{{-- ===== 메인 헤더 (로고 + 검색) ===== --}}
<header class="mg-head">
    <a href="{{ route('home') }}" class="mg-logo" aria-label="망고샵 홈">
        <span class="mark">🥭</span>
        <span class="txt"><b>망고샵</b><small>MANGOSHOP · 수입과일 오픈마켓</small></span>
    </a>

    <div class="mg-search">
        <form method="GET" action="{{ route('catalog.search') }}">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="원산지·품종·수입사로 검색 (예: 태국 애플망고)" aria-label="상품 검색">
            <button type="submit" aria-label="검색"><x-icon name="search"/></button>
        </form>
        <div class="mg-pop">
            <b>인기검색어</b>
            @foreach(($site['popular_keywords'] ?? []) as $kw)
                <a href="{{ route('catalog.search', ['q' => $kw]) }}">{{ $kw }}</a>
            @endforeach
        </div>
    </div>
</header>

{{-- ===== GNB 과일 카테고리 바 ===== --}}
<nav class="mg-gnb" aria-label="카테고리">
    <div class="mg-wrap">
        @php($__catIcons = ['mango'=>'cat-mango','avocado'=>'cat-avocado','tropical'=>'cat-pineapple','citrus'=>'cat-citrus','berry'=>'cat-berry','giftset'=>'cat-gift'])
        <ul class="mg-gnb-list">
            @foreach($navCategories as $cat)
                <li><a href="{{ route('catalog.category', $cat->slug) }}"><span class="ic"><x-icon :name="$__catIcons[$cat->slug] ?? 'grid'" :size="19"/></span>{{ $cat->name }}</a></li>
            @endforeach
            <li><a href="{{ route('catalog.index', ['grade' => 'wholesale']) }}" class="biz"><span class="ic"><x-icon name="building" :size="19"/></span>사업자 전용몰</a></li>
        </ul>
    </div>
</nav>
