@extends('layouts.admin')
@section('title', '세금계산서 이력')
@section('heading', '세금계산서 발행 이력')

@section('content')
@php($cur = request('status'))
@php($labels = ['issued'=>'발행완료','simulated'=>'시뮬레이트','cancelled'=>'취소','failed'=>'실패'])

{{-- 요약 타일 --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px">
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">발행완료</div><div style="font-size:22px;font-weight:800;color:var(--a-navy)">{{ number_format($stats['issued']) }}건</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">발행금액 합계</div><div style="font-size:22px;font-weight:800">{{ number_format($stats['amount']) }}원</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">취소</div><div style="font-size:22px;font-weight:800;color:#e0322d">{{ number_format($stats['cancelled']) }}건</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">실패</div><div style="font-size:22px;font-weight:800;color:#c2410c">{{ number_format($stats['failed']) }}건</div></div>
</div>

<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.taxinvoice.index') }}" class="{{ !$cur ? 'on' : '' }}">전체</a>
        @foreach($labels as $k => $label)
            <a href="{{ route('admin.taxinvoice.index', ['status'=>$k]) }}" class="{{ $cur===$k ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="spacer"></div>
    <form method="GET" class="search-mini">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="상호/사업자번호/주문번호/승인번호">
        <button><x-icon name="search" :size="16"/></button>
    </form>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr>
            <th style="width:130px">발행일</th><th style="width:90px">구분</th><th>주문번호</th><th>공급받는자</th>
            <th style="text-align:right;width:120px">합계금액</th><th style="width:150px">승인번호</th><th style="width:90px">상태</th><th style="width:90px"></th>
        </tr></thead>
        <tbody>
        @forelse($invoices as $ti)
            <tr>
                <td>{{ optional($ti->issued_at ?: $ti->created_at)->format('Y.m.d H:i') }}</td>
                <td><span class="pill {{ $ti->invoice_kind==='plain'?'pill-w':'pill-y' }}">{{ $ti->invoice_kind==='plain'?'면세':'과세' }}</span></td>
                <td>
                    @if($ti->order)<a href="{{ route('admin.orders.show', $ti->order) }}" style="color:var(--a-navy);font-weight:600">{{ $ti->order->order_no }}</a>
                    @else <span class="muted">-</span>@endif
                </td>
                <td>{{ $ti->receiver_corp_name ?: '-' }}<div style="font-size:11.5px;color:#97a0b8">{{ $ti->receiver_corp_num }}</div></td>
                <td style="text-align:right"><b>{{ number_format($ti->total_amount) }}원</b><div style="font-size:11px;color:#97a0b8">공급 {{ number_format($ti->supply_amount) }} · 세 {{ number_format($ti->tax_amount) }}</div></td>
                <td style="font-size:12px">{{ $ti->nts_confirm_num ?: '-' }}</td>
                <td>
                    @if($ti->status==='cancelled')<span class="pill pill-n">취소</span>
                    @elseif($ti->status==='simulated')<span class="pill pill-w">시뮬레이트</span>
                    @elseif($ti->status==='issued')<span class="pill pill-y">발행완료</span>
                    @else<span class="pill pill-n">실패</span>@endif
                </td>
                <td style="text-align:right">
                    @if($ti->status==='issued')
                        <a href="{{ route('admin.taxinvoice.popup', $ti) }}" target="_blank" class="abtn abtn-ghost abtn-sm">원본</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center;color:#97a0b8;padding:40px">발행 이력이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $invoices->links('pagination.simple') }}
@endsection
