@extends('layouts.seller')
@section('title', '정산내역')
@section('heading', '정산내역')

@section('content')
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:18px">
    <div class="adm-stat"><div class="ic"><x-icon name="coin"/></div><div><div class="v">{{ number_format($summary['pending']) }}<span style="font-size:14px">원</span></div><div class="l">정산 예정</div></div></div>
    <div class="adm-stat"><div class="ic"><x-icon name="check"/></div><div><div class="v">{{ number_format($summary['settled']) }}<span style="font-size:14px">원</span></div><div class="l">정산 완료</div></div></div>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th>일자</th><th>주문번호</th><th style="text-align:right">판매액</th><th style="text-align:right">수수료</th><th style="text-align:right">정산액</th><th>상태</th></tr></thead>
        <tbody>
        @forelse($settlements as $s)
            <tr>
                <td>{{ $s->created_at->format('Y.m.d') }}</td>
                <td>{{ $s->order->order_no ?? '-' }}</td>
                <td style="text-align:right">{{ number_format($s->gross_amount) }}원</td>
                <td style="text-align:right;color:#97a0b8">-{{ number_format($s->commission_amount) }}원</td>
                <td style="text-align:right;font-weight:800">{{ number_format($s->net_amount) }}원</td>
                <td><span class="pill {{ $s->status==='settled'?'pill-y':'pill-w' }}">{{ $s->status==='settled'?'정산완료':'정산예정' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#97a0b8;padding:34px">정산 내역이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $settlements->links('pagination.simple') }}
@endsection
