@extends('layouts.app')
@section('title', $seller->name.' - 망고샵 수입사')

@section('content')
{{-- 수입사 헤더 --}}
<div style="background:linear-gradient(135deg,var(--mg-accent),#12864a);color:#fff">
    <div class="mg-wrap" style="padding:34px 16px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        <div style="font-size:56px">🏬</div>
        <div style="flex:1;min-width:200px">
            <h1 style="font-size:26px;font-weight:900;margin:0 0 6px">{{ $seller->name }}</h1>
            <p style="margin:0;opacity:.95">{{ $seller->intro }}</p>
            <div style="margin-top:10px;font-size:13px;opacity:.92;display:flex;gap:16px;flex-wrap:wrap">
                <span>주력 원산지 · <b>{{ $seller->origin_focus ?: '-' }}</b></span>
                <span>대표 · {{ $seller->ceo_name ?: '-' }}</span>
                <span>⭐ {{ $seller->avgRating() ?: '-' }} ({{ $seller->rating_count }})</span>
            </div>
        </div>
        <div style="text-align:right;font-size:13px;line-height:1.9">
            <div>기본 배송비 {{ number_format($seller->shipping_fee) }}원</div>
            @if($seller->free_shipping_threshold)<div>{{ number_format($seller->free_shipping_threshold) }}원↑ 무료배송</div>@endif
            @if($seller->coldchain)<div>❄️ 콜드체인 냉장배송</div>@endif
        </div>
    </div>
</div>

@if($seller->shipping_notice)
<div class="mg-strip"><div class="mg-wrap" style="justify-content:flex-start"><div class="item">🚚 <b>배송 안내</b> {{ $seller->shipping_notice }}</div></div></div>
@endif

<div class="mg-section">
    <div class="mg-wrap">
        <div class="mg-sec-head" style="text-align:left;margin-bottom:20px">
            <h3 style="font-size:22px">{{ $seller->name }} 상품 <span style="color:var(--mg-primary)">{{ $products->total() }}</span></h3>
        </div>
        @if($products->count())
            <div class="mg-grid">
                @foreach($products as $p)<x-product-card :product="$p"/>@endforeach
            </div>
            <div style="margin-top:30px">{{ $products->links('pagination.simple') }}</div>
        @else
            <p style="text-align:center;color:var(--mg-muted);padding:40px 0">등록된 상품이 없습니다.</p>
        @endif
    </div>
</div>
@endsection
