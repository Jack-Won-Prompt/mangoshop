@extends('layouts.seller')
@section('title', '스토어 설정')
@section('heading', '스토어 설정')

@section('content')
<div class="adm-card" style="max-width:720px">
    <div class="h">스토어 정보</div>
    <form method="POST" action="{{ route('seller.center.store.update') }}" style="padding:20px">
        @csrf @method('PUT')

        <div class="afield" style="margin:0 0 14px"><label>수입사명</label><input type="text" class="ainput" value="{{ $seller->name }}" disabled style="background:#f7f9fc"></div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="afield" style="margin:0"><label>대표자명</label><input type="text" name="ceo_name" class="ainput" value="{{ old('ceo_name', $seller->ceo_name) }}"></div>
            <div class="afield" style="margin:0"><label>주력 원산지</label><input type="text" name="origin_focus" class="ainput" value="{{ old('origin_focus', $seller->origin_focus) }}"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="afield" style="margin:0"><label>연락처</label><input type="text" name="phone" class="ainput" value="{{ old('phone', $seller->phone) }}"></div>
            <div class="afield" style="margin:0"><label>이메일</label><input type="email" name="email" class="ainput" value="{{ old('email', $seller->email) }}"></div>
        </div>

        <div class="afield" style="margin-top:12px"><label>스토어 소개</label><textarea name="intro" class="atextarea" rows="3">{{ old('intro', $seller->intro) }}</textarea></div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
            <div class="afield" style="margin:0"><label>기본 배송비(원)</label><input type="number" name="shipping_fee" class="ainput" value="{{ old('shipping_fee', $seller->shipping_fee) }}"></div>
            <div class="afield" style="margin:0"><label>무료배송 기준(원)</label><input type="number" name="free_shipping_threshold" class="ainput" value="{{ old('free_shipping_threshold', $seller->free_shipping_threshold) }}"></div>
        </div>
        <div class="afield" style="margin-top:12px"><label>배송 안내문</label><textarea name="shipping_notice" class="atextarea" rows="2">{{ old('shipping_notice', $seller->shipping_notice) }}</textarea></div>

        <button class="abtn abtn-pri" style="width:100%;justify-content:center;margin-top:16px">저장하기</button>
    </form>
</div>
@endsection
