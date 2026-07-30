@php($site = config('site'))
<div style="font-family:'Malgun Gothic','Apple SD Gothic Neo',sans-serif;font-size:14px;color:#2b2113;line-height:1.7;max-width:560px;margin:0 auto">
    <div style="background:linear-gradient(135deg,#ff8a1e,#ffb347);border-radius:14px 14px 0 0;padding:22px 24px;color:#fff">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.5px">🥭 {{ $site['name'] ?? '망고샵' }}</div>
        <div style="opacity:.92;margin-top:4px">거래명세서를 보내드립니다</div>
    </div>
    <div style="border:1px solid #f0e6d6;border-top:0;border-radius:0 0 14px 14px;padding:24px">
        <p style="margin:0 0 14px"><b>{{ $order->user->company_name ?? $order->receiver_name }}</b> 님, 안녕하세요.<br>
            주문 <b>{{ $order->order_no }}</b> 건의 거래명세서를 첨부해 드립니다.</p>

        <table style="width:100%;border-collapse:collapse;font-size:13.5px;margin:8px 0 16px">
            <tr><td style="padding:7px 0;color:#8a7a5f">주문번호</td><td style="padding:7px 0;text-align:right"><b>{{ $order->order_no }}</b></td></tr>
            <tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">주문일</td><td style="padding:7px 0;text-align:right;border-top:1px solid #f2eadd">{{ optional($order->paid_at ?: $order->created_at)->format('Y.m.d') }}</td></tr>
            <tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">상품</td><td style="padding:7px 0;text-align:right;border-top:1px solid #f2eadd">{{ optional($order->items->first())->product_name }}@if($order->items->count()>1) 외 {{ $order->items->count()-1 }}건 @endif</td></tr>
            <tr><td style="padding:9px 0;color:#8a7a5f;border-top:2px solid #ecdcc4;font-weight:700">합계금액</td><td style="padding:9px 0;text-align:right;border-top:2px solid #ecdcc4;font-size:17px;font-weight:800;color:#e8730a">{{ number_format($order->total) }}원</td></tr>
        </table>

        <div style="background:#fff8ef;border-radius:10px;padding:12px 14px;font-size:12.5px;color:#8a7a5f">
            📎 첨부된 PDF 파일에서 상세 거래명세서를 확인하실 수 있습니다.
        </div>

        <hr style="border:0;border-top:1px solid #f0e6d6;margin:20px 0">
        <div style="font-size:12px;color:#a99a80;line-height:1.8">
            <b>{{ $site['company'] ?? '망고샵' }}</b> · 대표 {{ $site['ceo'] ?? '' }} · 사업자 {{ $site['biz_no'] ?? '' }}<br>
            {{ $site['address'] ?? '' }}<br>
            고객센터 {{ $site['cs_tel'] ?? '' }} · {{ $site['email'] ?? '' }}
        </div>
    </div>
</div>
