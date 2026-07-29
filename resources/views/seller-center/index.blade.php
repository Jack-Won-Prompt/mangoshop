@extends('layouts.seller')
@section('title', '대시보드')
@section('heading', '대시보드')

@section('content')
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
    <div class="adm-stat"><div class="ic"><x-icon name="package"/></div><div><div class="v">{{ number_format($stats['products']) }}</div><div class="l">등록 상품</div></div></div>
    <div class="adm-stat"><div class="ic"><x-icon name="check"/></div><div><div class="v">{{ number_format($stats['onsale']) }}</div><div class="l">판매중</div></div></div>
    <div class="adm-stat"><div class="ic"><x-icon name="cart"/></div><div><div class="v">{{ number_format($stats['orders']) }}</div><div class="l">주문</div></div></div>
    <div class="adm-stat"><div class="ic"><x-icon name="coin"/></div><div><div class="v">{{ number_format($stats['settlement']) }}<span style="font-size:14px">원</span></div><div class="l">정산 예정</div></div></div>
</div>

<div class="adm-card">
    <div class="h">스토어 정보</div>
    <div style="padding:18px 20px;display:flex;align-items:center;gap:16px">
        <div style="flex:none;width:52px;height:52px;border-radius:13px;background:#fff4ea;display:flex;align-items:center;justify-content:center;font-size:28px">🏬</div>
        <div style="flex:1">
            <div style="font-size:17px;font-weight:900">{{ $seller->name }}
                @if($seller->status==='approved')<span class="pill pill-y">승인</span>@else<span class="pill pill-w">{{ $seller->status }}</span>@endif
            </div>
            <div style="color:#6b7794;font-size:13px;margin-top:3px">{{ $seller->origin_focus }} · 수수료 {{ rtrim(rtrim(number_format($seller->commission_rate,2),'0'),'.') }}%</div>
        </div>
        <a href="{{ route('seller.center.store') }}" class="abtn abtn-ghost abtn-sm">스토어 설정</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="adm-card">
        <div class="h">내 상품 <a href="{{ route('seller.center.products') }}" class="abtn abtn-ghost abtn-sm">전체보기</a></div>
        <table class="atable">
            <thead><tr><th>상품명</th><th style="text-align:right">판매가</th><th style="text-align:right">재고</th></tr></thead>
            <tbody>
            @forelse($products as $p)
                <tr><td><b>{{ $p->name }}</b></td><td style="text-align:right">{{ number_format($p->price) }}원</td><td style="text-align:right">{{ $p->stock }}</td></tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#97a0b8;padding:24px">등록된 상품이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="adm-card">
        <div class="h">최근 주문 <a href="{{ route('seller.center.orders') }}" class="abtn abtn-ghost abtn-sm">전체보기</a></div>
        <table class="atable">
            <thead><tr><th>주문번호</th><th style="text-align:right">금액</th><th>상태</th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr><td>{{ $o->order_no }}</td><td style="text-align:right">{{ number_format($o->total) }}원</td><td><span class="pill pill-b">{{ $o->statusLabel() }}</span></td></tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#97a0b8;padding:24px">아직 주문이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
