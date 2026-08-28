@extends('layouts.admin')
@section('title', '상품관리')
@section('heading', '상품관리')

@section('content')
@php($statuses = ['on_sale'=>'판매중','soldout'=>'품절','closed'=>'판매중지','inbound'=>'입고예정'])
@php($state = request('state'))

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px">
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">전체 상품</div><div style="font-size:22px;font-weight:800">{{ number_format($stats['total']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">판매중</div><div style="font-size:22px;font-weight:800;color:var(--a-navy)">{{ number_format($stats['onsale']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">미노출</div><div style="font-size:22px;font-weight:800;color:#e0322d">{{ number_format($stats['hidden']) }}</div></div>
</div>

<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.products.index') }}" class="{{ !$state ? 'on' : '' }}">전체</a>
        <a href="{{ route('admin.products.index', ['state'=>'onsale']) }}" class="{{ $state==='onsale' ? 'on' : '' }}">판매중</a>
        <a href="{{ route('admin.products.index', ['state'=>'soldout']) }}" class="{{ $state==='soldout' ? 'on' : '' }}">품절</a>
        <a href="{{ route('admin.products.index', ['state'=>'hidden']) }}" class="{{ $state==='hidden' ? 'on' : '' }}">미노출</a>
    </div>
    <div class="spacer"></div>
    <form method="GET" class="search-mini">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="상품명/코드/제조사">
        <button><x-icon name="search" :size="16"/></button>
    </form>
    <a href="{{ route('admin.products.create') }}" class="abtn abtn-pri abtn-sm"><x-icon name="plus" :size="15"/> 상품등록</a>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th style="width:60px">이미지</th><th>상품명</th><th style="width:130px">카테고리</th><th style="text-align:right;width:110px">판매가</th><th style="text-align:right;width:70px">재고</th><th style="width:90px">상태</th><th style="width:120px"></th></tr></thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td>
                    @if($p->thumbnail)<img src="{{ \App\Support\Media::url($p->thumbnail) }}" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px">
                    @else<div style="width:44px;height:44px;border-radius:8px;background:#f7f9fc;display:flex;align-items:center;justify-content:center;color:#c7cedd"><x-icon name="box" :size="18"/></div>@endif
                </td>
                <td><b>{{ $p->name }}</b><div style="color:#97a0b8;font-size:11.5px">{{ $p->code }} · {{ $p->maker }}</div></td>
                <td style="font-size:12.5px">{{ $p->category?->name }}</td>
                <td style="text-align:right">
                    @if($p->is_quote)<span style="color:#c9640a;font-weight:700">가격문의</span>
                    @else{{ number_format($p->price) }}원@endif
                </td>
                <td style="text-align:right">{{ number_format($p->stock) }}</td>
                <td>
                    @php($st = $p->sale_status)
                    @if(!$p->is_active)<span class="pill pill-n">미노출</span>
                    @else<span class="pill {{ $st==='on_sale'?'pill-y':($st==='soldout'||$st==='closed'?'pill-n':'pill-w') }}">{{ $statuses[$st] ?? $st }}</span>@endif
                </td>
                <td style="text-align:right;white-space:nowrap">
                    <form method="POST" action="{{ route('admin.products.togglequote', $p) }}" style="display:inline" title="가격문의 전환">@csrf
                        <button class="abtn abtn-ghost abtn-sm" type="submit" style="{{ $p->is_quote ? 'color:#c9640a;border-color:#f0b37e;font-weight:700' : '' }}">가격문의{{ $p->is_quote ? ' ✓' : '' }}</button>
                    </form>
                    <a href="{{ route('admin.products.edit', $p) }}" class="abtn abtn-ghost abtn-sm">수정</a>
                    <form method="POST" action="{{ route('admin.products.destroy', $p) }}" style="display:inline" onsubmit="return confirm('삭제하시겠습니까?')">@csrf @method('DELETE')
                        <button class="abtn abtn-ghost abtn-sm" type="submit">삭제</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#97a0b8;padding:36px">상품이 없습니다. <a href="{{ route('admin.products.create') }}" style="color:var(--a-navy);font-weight:700">첫 상품 등록 →</a></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $products->links('pagination.simple') }}
@endsection
