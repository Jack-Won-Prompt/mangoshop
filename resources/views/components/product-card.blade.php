@props(['product'])
@php
    $user = auth()->user();
    $canSee = $product->priceVisibleFor($user);      // 가격 노출 여부
    $sell = $product->priceFor($user);
    $isWholesale = $user && $user->isWholesale();
    $special = $canSee && $isWholesale && $sell < $product->price;   // 도매가/전용가 적용
    $rate = ($canSee && $product->price > 0 && $sell < $product->price)
        ? (int) round(($product->price - $sell) / $product->price * 100) : 0;
    $status = $product->sale_status;
    $soldout = $status === 'soldout' || ($status === 'on_sale' && $product->stock <= 0);
    $inbound = $status === 'inbound';
    $closed  = $status === 'closed';
    $inWish = in_array($product->id, $wishlistIds ?? []);
    $emoji = $product->category?->icon ?: '🥭';
    $tierMin = collect($product->price_tiers ?? [])->min('min_qty');
@endphp
<div class="mg-card">
    @auth
        <form method="POST" action="{{ route('wishlist.toggle', $product) }}" style="position:absolute;top:0;right:0;z-index:3">
            @csrf
            <button type="submit" class="mg-wish {{ $inWish ? 'on' : '' }}" aria-label="관심상품"><x-icon name="heart" :size="16"/></button>
        </form>
    @else
        <a href="{{ route('login') }}" class="mg-wish" aria-label="관심상품(로그인)"><x-icon name="heart" :size="16"/></a>
    @endauth

    <a href="{{ route('catalog.show', $product->slug) }}" class="mg-thumb">
        <div class="mg-badges">
            @if($product->origin)<span class="mg-badge origin">{{ $product->origin }}</span>@endif
            @if($product->is_best)<span class="mg-badge best">BEST</span>@endif
            @if($product->is_new)<span class="mg-badge new">NEW</span>@endif
            @if($inbound)<span class="mg-badge inbound">입고예정</span>@endif
        </div>
        @if($product->thumbnail)
            <img src="{{ $product->thumbnail }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="mg-thumb-ph" style="background:linear-gradient(135deg,#fff4ea,#ffe6cc)">{{ $emoji }}</span>
        @endif
        @if($soldout)<span class="mg-soldout-cover">품절</span>@endif
        @if($closed)<span class="mg-soldout-cover">당일마감</span>@endif
    </a>

    <div class="mg-info">
        @if($product->seller)<div class="mg-seller">{{ $product->seller->name }}</div>@endif
        <a href="{{ route('catalog.show', $product->slug) }}" class="mg-name">{{ $product->name }}</a>
        <div class="mg-meta">
            @if($product->variety)<span>{{ $product->variety }}</span>@endif
            @if($product->grade)<span>{{ $product->grade }}등급</span>@endif
            @if($product->box_spec)<span>{{ $product->box_spec }}</span>@endif
        </div>
        <div class="mg-price-row">
            @if(! $canSee)
                <div class="mg-price-hide">
                    @guest 로그인 후 가격 확인 @else 회원 승인 후 가격 확인 @endguest
                </div>
            @elseif($sell <= 0)
                <div class="mg-price-hide"><a href="{{ route('community.inquiry', ['type' => 'quote', 'product' => $product->id]) }}">가격문의</a></div>
            @else
                @if($rate > 0)<span class="mg-oprice">{{ number_format($product->price) }}원</span>@endif
                <div style="display:flex;align-items:baseline;gap:6px;flex-wrap:wrap">
                    @if($rate > 0)<span class="mg-rate">{{ $rate }}%</span>@endif
                    <span class="mg-price">{{ number_format($sell) }}<span class="won">원</span></span>
                </div>
                @if($special)<div class="mg-wprice">도매 전용가 적용중</div>
                @elseif(! $isWholesale && $product->wholesale_price)<div class="mg-wprice">도매회원 전용가 별도</div>@endif
                @if($tierMin)<div class="mg-wprice" style="color:var(--mg-primary)">{{ $tierMin }}박스↑ 추가할인</div>@endif
            @endif
        </div>
    </div>

    <div class="mg-actions">
        @if($inbound)
            <form method="POST" action="{{ route('restock.toggle', $product) }}" style="flex:1">@csrf
                <button class="mg-btn gho block" type="submit">🔔 재입고 알림</button>
            </form>
        @elseif($soldout || $closed)
            <button class="mg-btn gho block" disabled>{{ $closed ? '판매마감' : '품절' }}</button>
        @elseif(! $canSee)
            <a href="{{ route(auth()->check() ? 'catalog.show' : 'login', auth()->check() ? $product->slug : []) }}" class="mg-btn gho block">가격 확인</a>
        @else
            <a href="{{ route('catalog.show', $product->slug) }}" class="mg-btn gho">상세</a>
            <form method="POST" action="{{ route('cart.add', $product) }}" style="flex:1">@csrf
                <button class="mg-btn pri" type="submit"><x-icon name="cart" :size="15"/>담기</button>
            </form>
        @endif
    </div>
</div>
