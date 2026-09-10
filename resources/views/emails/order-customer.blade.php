@php($site = config('site'))
@php($isBank = $mode === 'bank')
<div style="font-family:'Malgun Gothic','Apple SD Gothic Neo',sans-serif;font-size:14px;color:#2b2113;line-height:1.7;max-width:600px;margin:0 auto">
    <div style="background:linear-gradient(135deg,#ff8a1e,#ffb347);border-radius:14px 14px 0 0;padding:22px 24px;color:#fff">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.5px">🥭 {{ $site['name'] ?? '망고샵' }}</div>
        <div style="opacity:.92;margin-top:4px">{{ $isBank ? '주문이 접수되었습니다 · 입금 안내' : '결제가 완료되었습니다' }}</div>
    </div>
    <div style="border:1px solid #f0e6d6;border-top:0;border-radius:0 0 14px 14px;padding:24px">

        <table style="width:100%;border-collapse:collapse;font-size:13.5px;margin:0 0 14px">
            <tr><td style="padding:6px 0;color:#8a7a5f;width:90px">주문번호</td><td style="padding:6px 0"><b>{{ $order->order_no }}</b></td></tr>
            <tr><td style="padding:6px 0;color:#8a7a5f;border-top:1px solid #f2eadd">주문일시</td><td style="padding:6px 0;border-top:1px solid #f2eadd">{{ $order->created_at->format('Y.m.d H:i') }}</td></tr>
            <tr><td style="padding:6px 0;color:#8a7a5f;border-top:1px solid #f2eadd">결제수단</td><td style="padding:6px 0;border-top:1px solid #f2eadd">{{ $isBank ? '무통장 입금' : ($order->pay_method ?: '카드/간편결제') }}</td></tr>
        </table>

        {{-- 상품 --}}
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin:0 0 12px">
            <thead><tr style="background:#fff8ef"><th style="text-align:left;padding:8px 10px;color:#8a7a5f;font-weight:600">상품</th><th style="text-align:center;padding:8px;color:#8a7a5f;font-weight:600;width:44px">수량</th><th style="text-align:right;padding:8px 10px;color:#8a7a5f;font-weight:600;width:90px">금액</th></tr></thead>
            <tbody>
                @foreach($items as $it)
                    <tr style="border-top:1px solid #f2eadd">
                        <td style="padding:9px 10px">{{ $it->display_name ?? $it->product_name }}</td>
                        <td style="padding:9px;text-align:center">{{ $it->quantity }}</td>
                        <td style="padding:9px 10px;text-align:right">{{ number_format($it->subtotal) }}원</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="text-align:right;border-top:2px solid #f0e6d6;padding-top:10px;margin-bottom:18px">
            <span style="color:#8a7a5f">결제금액 </span><b style="font-size:19px;color:#e0322d">{{ number_format($amount) }}원</b>
        </div>

        @if($isBank)
            {{-- 입금 안내 --}}
            <div style="background:#fff6ef;border:1px solid #ffe0c4;border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:13.5px;line-height:1.9">
                <b style="color:#c9640a">입금 계좌 안내</b><br>
                @if($order->va_account)
                    {{ $order->va_bank }} <b>{{ $order->va_account }}</b> (예금주 {{ $order->va_holder ?: ($site['company'] ?? '망고샵') }})<br>
                @elseif(!empty($site['banks']))
                    @foreach((array) $site['banks'] as $b)
                        {{ is_array($b) ? ($b['bank'].' '.$b['account'].' ('.($b['holder'] ?? '').')') : $b }}<br>
                    @endforeach
                @endif
                입금자명 <b>{{ $order->depositor ?: $order->receiver_name }}</b> · 입금 확인 후 배송이 시작됩니다.
            </div>
        @elseif($order->receipt_url)
            {{-- 영수증 --}}
            <div style="text-align:center;margin:6px 0 18px">
                <a href="{{ $order->receipt_url }}" style="display:inline-block;background:#0b3d91;color:#fff;font-weight:700;font-size:14px;text-decoration:none;padding:12px 28px;border-radius:26px">🧾 결제 영수증 보기 →</a>
            </div>
        @endif

        {{-- 배송지 --}}
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <tr><td style="padding:5px 0;color:#8a7a5f;width:90px">받는분</td><td style="padding:5px 0">{{ $order->receiver_name }} · {{ $order->receiver_phone }}</td></tr>
            <tr><td style="padding:5px 0;color:#8a7a5f;border-top:1px solid #f2eadd">배송지</td><td style="padding:5px 0;border-top:1px solid #f2eadd">@if($order->postcode)({{ $order->postcode }}) @endif{{ $order->address1 }} {{ $order->address2 }}</td></tr>
        </table>

        <div style="text-align:center;margin:22px 0 6px">
            <a href="{{ route('order.complete', $order) }}" style="display:inline-block;background:#ff6b00;color:#fff;font-weight:800;font-size:15px;text-decoration:none;padding:13px 32px;border-radius:30px">주문 상세 보기 →</a>
        </div>

        <hr style="border:0;border-top:1px solid #f0e6d6;margin:20px 0">
        <div style="font-size:12px;color:#a99a80;line-height:1.8">
            <b>{{ $site['company'] ?? '망고샵' }}</b> · 고객센터 {{ $site['cs_tel'] ?? '' }}<br>
            결제 후 당일 발송되며 지역에 따라 도착까지 1~2일 소요됩니다.
        </div>
    </div>
</div>
