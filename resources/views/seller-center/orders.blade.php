@extends('layouts.seller')
@section('title', '주문관리')
@section('heading', '주문관리')

@section('content')
<div class="adm-card">
    <table class="atable">
        <thead><tr><th>주문번호</th><th>주문일</th><th>수령자</th><th style="text-align:right">금액</th><th>상태</th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td><b>{{ $o->order_no }}</b></td>
                <td>{{ $o->created_at->format('Y.m.d H:i') }}</td>
                <td>{{ $o->receiver_name }}</td>
                <td style="text-align:right">{{ number_format($o->total) }}원</td>
                <td><span class="pill pill-b">{{ $o->statusLabel() }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#97a0b8;padding:34px">아직 주문이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $orders->links('pagination.simple') }}
@endsection
