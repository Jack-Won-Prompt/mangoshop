@extends('layouts.seller')
@section('title', '상품관리')
@section('heading', '상품관리')

@section('content')
@php($statuses = ['on_sale'=>'판매중','soldout'=>'품절','closed'=>'판매중지','inbound'=>'입고예정'])

<div class="toolbar">
    <div class="filter-tabs"><span style="color:#6b7794;font-size:13px;padding:7px 0">총 {{ $products->total() }}개 상품</span></div>
    <div class="spacer"></div>
    <form method="GET" class="search-mini">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="상품명 검색">
        <button><x-icon name="search" :size="16"/></button>
    </form>
    <a href="{{ route('seller.center.products.create') }}" class="abtn abtn-pri abtn-sm"><x-icon name="plus" :size="15"/> 상품등록</a>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th style="width:64px">이미지</th><th>상품명</th><th style="text-align:right;width:110px">판매가</th><th style="text-align:right;width:80px">재고</th><th style="width:90px">상태</th><th style="width:130px"></th></tr></thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td>
                    @if($p->thumbnail)<img src="{{ $p->thumb_url }}" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px">
                    @else<div style="width:44px;height:44px;border-radius:8px;background:#f7f9fc;display:flex;align-items:center;justify-content:center;color:#c7cedd"><x-icon name="package" :size="18"/></div>@endif
                </td>
                <td><b>{{ $p->name }}</b><div style="color:#97a0b8;font-size:11.5px">{{ $p->code }} · {{ $p->origin }} {{ $p->box_spec }}</div></td>
                <td style="text-align:right">{{ number_format($p->price) }}원</td>
                <td style="text-align:right">{{ $p->stock }}</td>
                <td>
                    @php($st = $p->sale_status)
                    <span class="pill {{ $st==='on_sale'?'pill-y':($st==='soldout'||$st==='closed'?'pill-n':'pill-w') }}">{{ $statuses[$st] ?? $st }}</span>
                </td>
                <td style="text-align:right;white-space:nowrap">
                    <a href="{{ route('seller.center.products.edit', $p) }}" class="abtn abtn-ghost abtn-sm">수정</a>
                    <form method="POST" action="{{ route('seller.center.products.destroy', $p) }}" style="display:inline" onsubmit="return confirm('삭제하시겠습니까?')">@csrf @method('DELETE')
                        <button class="abtn abtn-ghost abtn-sm" type="submit">삭제</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#97a0b8;padding:34px">등록된 상품이 없습니다. <a href="{{ route('seller.center.products.create') }}" style="color:var(--a-navy);font-weight:700">첫 상품 등록하기 →</a></td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $products->links('pagination.simple') }}
@endsection
