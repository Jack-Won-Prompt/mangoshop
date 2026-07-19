<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
    <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
    <title>@yield('title', $site['name'].' — '.$site['tagline'])</title>
    <meta name="description" content="@yield('desc', '수입 과일 도매·소매 오픈마켓 망고샵 — 태국·베트남·필리핀 애플망고, 아보카도, 열대과일 등 검증된 수입사의 과일을 만나보세요.')">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%A5%AD%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=19">
    <link rel="stylesheet" href="{{ asset('css/mango.css') }}?v=5">
    @stack('head')
</head>
<body>
    @include('partials.icons')
    @include('partials.header')

    <main>
        @if(session('ok') || session('error') || $errors->any())
            <div class="container" style="padding-top:18px">
                @if(session('ok'))<div class="flash"><x-icon name="check"/>{{ session('ok') }}</div>@endif
                @if(session('error'))<div class="flash err"><x-icon name="close"/>{{ session('error') }}</div>@endif
                @if($errors->any())<div class="flash err"><x-icon name="close"/>{{ $errors->first() }}</div>@endif
            </div>
        @endif
        @yield('content')
    </main>

    @include('partials.footer')

    {{-- 모바일 하단 고정 네비 (모바일에서만 노출) --}}
    @php($__nav = request()->path())
    <nav class="mobile-nav" aria-label="모바일 하단 메뉴">
        <a href="{{ url('/') }}" class="{{ $__nav === '/' || $__nav === '' ? 'on' : '' }}"><x-icon name="home"/><span>홈</span></a>
        <a href="{{ route('catalog.index') }}" class="{{ str_starts_with($__nav, 'products') || str_contains($__nav, 'category') ? 'on' : '' }}"><x-icon name="grid"/><span>카테고리</span></a>
        <a href="{{ route('cart.index') }}" class="{{ str_contains($__nav, 'cart') ? 'on' : '' }}"><x-icon name="cart"/>@if(($cartCount ?? 0) > 0)<span class="m-badge">{{ $cartCount }}</span>@endif<span>장바구니</span></a>
        <a href="{{ route('mypage.wishlist') }}" class="{{ str_contains($__nav, 'wishlist') ? 'on' : '' }}"><x-icon name="heart"/><span>관심상품</span></a>
        <a href="{{ route('mypage.index') }}" class="{{ str_starts_with($__nav, 'mypage') ? 'on' : '' }}"><x-icon name="user"/><span>마이페이지</span></a>
    </nav>

    @include('partials.recent')
    @include('partials.chat')
    <button class="to-top" aria-label="맨 위로"><x-icon name="arrow-right"/></button>
    <script src="{{ asset('js/site.js') }}?v=5" defer></script>
    @stack('scripts')
</body>
</html>
