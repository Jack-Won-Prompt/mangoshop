{{--
    거래명세서 PDF (dompdf 전용 · A4)
    - 공급자 정보는 사이트 설정(config('site'))에서 — 하드코딩 없음
    - 금액 규칙은 전자세금계산서와 동일: 판매가는 부가세 포함, 공급가액 = round(합계/1.1)
--}}
@php
    $s = config('site');
    $items = $order->items;
    $taxable = $isTaxable ?? true;
    $subtotal = (int) $order->subtotal;
    $ship = (int) $order->shipping_fee;
    $discount = (int) $order->discount + (int) $order->point_used;
    $total = (int) $order->total;
    $supply = $taxable ? (int) round($total / 1.1) : $total;
    $vat = $total - $supply;
    $buyerName = $order->user->company_name ?? ($order->buyer_name ?: $order->receiver_name);
    $buyerBiz  = $order->user->biz_no ?? $order->buyer_biz_no;
@endphp
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<style>
    @font-face { font-family:'nanum'; font-weight:normal; src:url('{{ storage_path('fonts/NanumGothic.ttf') }}') format('truetype'); }
    @font-face { font-family:'nanum'; font-weight:bold;   src:url('{{ storage_path('fonts/NanumGothicBold.ttf') }}') format('truetype'); }
    * { font-family:'nanum', sans-serif; }
    @page { margin: 24px 26px; }
    body { font-size:11px; color:#111; }
    h1 { font-size:20px; text-align:center; margin:0 0 2px; letter-spacing:6px; color:#c9640a; }
    .sub { text-align:center; font-size:10px; color:#555; margin-bottom:12px; }
    table { width:100%; border-collapse:collapse; }
    .box td, .box th { border:1px solid #444; padding:4px 6px; }
    .party .head { background:#fff1e0; font-weight:bold; text-align:center; }
    .party .lbl  { background:#fbf7f1; width:64px; color:#333; }
    .items th { background:#fff1e0; border:1px solid #444; padding:5px 4px; font-size:10.5px; }
    .items td { border:1px solid #999; padding:4px; font-size:10.5px; }
    .num { text-align:right; }
    .ctr { text-align:center; }
    .sum td { border:1px solid #444; padding:5px 6px; }
    .sum .lbl { background:#fbf7f1; width:90px; }
    .total { background:#fff1e0; font-weight:bold; font-size:13px; }
    .note { margin-top:10px; font-size:9.5px; color:#555; line-height:1.6; }
    .bank { margin-top:8px; font-size:10px; }
    .stamp { text-align:right; font-size:10px; margin-top:6px; color:#333; }
</style>
</head>
<body>

<h1>거 래 명 세 서</h1>
<div class="sub">
    주문번호 {{ $order->order_no }} ·
    거래일자 {{ \Illuminate\Support\Carbon::parse($statementDate)->format('Y년 m월 d일') }}
    @if(($seq ?? 1) > 1) · 재발행 {{ $seq }}회차 @endif
</div>

{{-- 공급자 / 공급받는 자 --}}
<table>
    <tr>
        <td style="width:50%; padding-right:6px; vertical-align:top">
            <table class="box party">
                <tr><td class="head" colspan="2">공급자</td></tr>
                <tr><td class="lbl">상호</td><td><b>{{ $s['company'] ?: $s['name'] }}</b></td></tr>
                <tr><td class="lbl">대표자</td><td>{{ $s['ceo'] ?: '-' }}</td></tr>
                <tr><td class="lbl">등록번호</td><td>{{ $s['biz_no'] ?: '-' }}</td></tr>
                <tr><td class="lbl">주소</td><td>{{ $s['address'] ?: '-' }}</td></tr>
                <tr><td class="lbl">연락처</td><td>{{ $s['cs_tel'] ?: '-' }}</td></tr>
            </table>
        </td>
        <td style="width:50%; padding-left:6px; vertical-align:top">
            <table class="box party">
                <tr><td class="head" colspan="2">공급받는 자</td></tr>
                <tr><td class="lbl">상호</td><td><b>{{ $buyerName }}</b></td></tr>
                <tr><td class="lbl">담당자</td><td>{{ $order->buyer_name ?: $order->receiver_name }}</td></tr>
                <tr><td class="lbl">등록번호</td><td>{{ $buyerBiz ?: '-' }}</td></tr>
                <tr><td class="lbl">주소</td><td>{{ trim(($order->postcode ? '('.$order->postcode.') ' : '').$order->address1.' '.$order->address2) ?: '-' }}</td></tr>
                <tr><td class="lbl">연락처</td><td>{{ $order->buyer_phone ?: $order->receiver_phone }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- 품목 --}}
<table class="items" style="margin-top:10px">
    <thead>
        <tr>
            <th style="width:26px">No</th>
            <th>품목</th>
            <th style="width:52px">단위</th>
            <th style="width:70px">단가</th>
            <th style="width:44px">수량</th>
            <th style="width:80px">공급가액</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $it)
            <tr>
                <td class="ctr">{{ $i + 1 }}</td>
                <td>{{ $it->display_name }}</td>
                <td class="ctr">{{ $it->unit ?: '-' }}</td>
                <td class="num">{{ number_format((int) $it->price) }}</td>
                <td class="ctr">{{ number_format((int) $it->quantity) }}</td>
                <td class="num">{{ number_format((int) $it->subtotal) }}</td>
            </tr>
        @endforeach
        {{-- 품목이 적을 때 표가 너무 짧아 보이지 않게 빈 줄 채움 --}}
        @for($n = $items->count(); $n < 8; $n++)
            <tr><td class="ctr">{{ $n + 1 }}</td><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

{{-- 합계 --}}
<table class="box sum" style="margin-top:10px">
    <tr>
        <td class="lbl">상품합계</td><td class="num" style="width:110px">{{ number_format($subtotal) }}원</td>
        <td class="lbl">배송비</td><td class="num" style="width:110px">{{ number_format($ship) }}원</td>
        <td class="lbl">할인·적립금</td><td class="num" style="width:110px">- {{ number_format($discount) }}원</td>
    </tr>
    <tr>
        <td class="lbl">공급가액</td><td class="num">{{ number_format($supply) }}원</td>
        <td class="lbl">부가세</td><td class="num">{{ number_format($vat) }}원</td>
        <td class="lbl total">합계금액</td><td class="num total">{{ number_format($total) }}원</td>
    </tr>
</table>

@if(! $taxable)
    <div class="note">※ 면세 품목으로 구성된 거래로 부가세가 없습니다.</div>
@endif

@if(! empty($s['banks']))
    <div class="bank">
        <b>입금계좌</b>
        @foreach($s['banks'] as $b)
            @if(! empty($b['account']))
                &nbsp; {{ $b['bank'] ?? '' }} {{ $b['account'] }} ({{ $b['holder'] ?? '' }})
            @endif
        @endforeach
    </div>
@endif

<div class="note">
    · 본 명세서는 주문내역을 기준으로 자동 생성된 문서입니다.
    · 결제수단 {{ in_array($order->payment_method, ['bank','vbank']) ? '무통장·가상계좌' : '카드·간편결제' }}
    · 주문상태 {{ $order->statusLabel() }}
    @if($order->memo) <br>· 요청사항: {{ $order->memo }} @endif
</div>

<div class="stamp">{{ $s['company'] ?: $s['name'] }} @if($s['cs_tel']) · {{ $s['cs_tel'] }} @endif</div>

</body>
</html>
