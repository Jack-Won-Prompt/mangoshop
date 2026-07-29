<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '판매자센터') — 망고샵 판매자</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
    <link rel="icon" href="{{ asset('images/logo-mark.svg') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=22">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=6">
</head>
<body>
    @include('partials.icons')
    <div class="adm">
        <aside class="adm-side">
            <a href="{{ route('seller.center.index') }}" class="adm-brand">
                <img src="{{ asset('images/logo-mark.svg') }}?v=2" alt="" class="mark" style="width:32px;height:32px;background:#fff;border-radius:7px;padding:2px">
                <span><strong>망고샵</strong><span>SELLER</span></span>
            </a>
            <nav class="adm-nav">
                <a href="{{ route('seller.center.index') }}" class="{{ request()->routeIs('seller.center.index') ? 'on' : '' }}"><x-icon name="chart"/> 대시보드</a>

                <div class="grp">판매관리</div>
                <a href="{{ route('seller.center.products') }}" class="{{ request()->routeIs('seller.center.products') ? 'on' : '' }}"><x-icon name="package"/> 상품관리</a>
                <a href="{{ route('seller.center.orders') }}" class="{{ request()->routeIs('seller.center.orders') ? 'on' : '' }}"><x-icon name="cart"/> 주문관리</a>
                <a href="{{ route('seller.center.settlements') }}" class="{{ request()->routeIs('seller.center.settlements') ? 'on' : '' }}"><x-icon name="coin"/> 정산내역</a>

                <div class="grp">스토어</div>
                <a href="{{ route('seller.center.store') }}" class="{{ request()->routeIs('seller.center.store') ? 'on' : '' }}"><x-icon name="tools"/> 스토어 설정</a>
                <a href="{{ route('seller.show', auth()->user()->seller->slug) }}" target="_blank"><x-icon name="arrow-right"/> 내 스토어 보기</a>
            </nav>
            <div class="adm-foot">
                <a href="{{ route('home') }}" target="_blank"><x-icon name="arrow-right"/> 쇼핑몰 보기</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit"><x-icon name="logout"/> 로그아웃</button>
                </form>
            </div>
        </aside>

        <div class="adm-main">
            <header class="adm-top">
                <h1>@yield('heading', '판매자센터')</h1>
                <div class="who"><x-icon name="user" :size="16"/> {{ auth()->user()->seller->name ?? auth()->user()->name }}</div>
            </header>
            <div class="adm-body">
                @if(session('ok'))<div class="flash"><x-icon name="check"/> {{ session('ok') }}</div>@endif
                @if(session('error'))<div class="flash" style="background:#fee2e2;border-color:#fecaca;color:#991b1b"><x-icon name="close"/> {{ session('error') }}</div>@endif
                @if($errors->any())<div class="flash" style="background:#fee2e2;border-color:#fecaca;color:#991b1b"><x-icon name="close"/> {{ $errors->first() }}</div>@endif
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
