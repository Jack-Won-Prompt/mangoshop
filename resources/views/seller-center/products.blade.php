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
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th>상품명</th><th style="width:120px">판매가</th><th style="width:120px">도매가</th><th style="width:90px">재고</th><th style="width:130px">판매상태</th><th style="width:70px"></th></tr></thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td>
                    <b>{{ $p->name }}</b>
                    <div style="color:#97a0b8;font-size:11.5px">{{ $p->code }} · {{ $p->origin }} {{ $p->box_spec }}</div>
                </td>
                <td><input form="pf{{ $p->id }}" type="number" name="price" value="{{ $p->price }}" class="ainput" style="height:34px;padding:4px 8px"></td>
                <td><input form="pf{{ $p->id }}" type="number" name="wholesale_price" value="{{ $p->wholesale_price }}" class="ainput" style="height:34px;padding:4px 8px"></td>
                <td><input form="pf{{ $p->id }}" type="number" name="stock" value="{{ $p->stock }}" class="ainput" style="height:34px;padding:4px 8px"></td>
                <td>
                    <select form="pf{{ $p->id }}" name="sale_status" class="aselect" style="height:34px;padding:4px 8px">
                        @foreach($statuses as $k=>$v)<option value="{{ $k }}" {{ $p->sale_status===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                    </select>
                </td>
                <td><button form="pf{{ $p->id }}" class="abtn abtn-pri abtn-sm" type="submit">저장</button></td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#97a0b8;padding:34px">상품이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- 행별 폼(HTML5 form 속성 연결) --}}
@foreach($products as $p)
    <form id="pf{{ $p->id }}" method="POST" action="{{ route('seller.center.products.update', $p) }}" style="display:none">@csrf @method('PUT')</form>
@endforeach

{{ $products->links('pagination.simple') }}
<p style="color:#97a0b8;font-size:12.5px;margin-top:12px">※ 판매가·도매가·재고·판매상태를 수정할 수 있습니다. 신규 상품 등록은 순차 제공됩니다.</p>
@endsection
