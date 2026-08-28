@php($site = config('site'))
<div style="font-family:'Malgun Gothic','Apple SD Gothic Neo',sans-serif;font-size:14px;color:#2b2113;line-height:1.7;max-width:600px;margin:0 auto">
    <div style="background:linear-gradient(135deg,#ff8a1e,#ffb347);border-radius:14px 14px 0 0;padding:22px 24px;color:#fff">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.5px">🥭 {{ $site['name'] ?? '망고샵' }}</div>
        <div style="opacity:.92;margin-top:4px">새 주문이 접수되었습니다</div>
    </div>
    <div style="border:1px solid #f0e6d6;border-top:0;border-radius:0 0 14px 14px;padding:24px">
        <p style="margin:0 0 14px">@if($sellerName)<b>{{ $sellerName }}</b> 님, @endif결제가 완료된 주문입니다. 아래 내용을 확인하고 <b>배송예정일·송장번호</b>를 입력해 주세요.</p>

        <table style="width:100%;border-collapse:collapse;font-size:13.5px;margin:8px 0 16px">
            <tr><td style="padding:7px 0;color:#8a7a5f;width:90px">주문번호</td><td style="padding:7px 0;text-align:right"><b>{{ $order->order_no }}</b></td></tr>
            <tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">주문일</td><td style="padding:7px 0;text-align:right;border-top:1px solid #f2eadd">{{ optional($order->paid_at ?: $order->created_at)->format('Y.m.d H:i') }}</td></tr>
            <tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">받는분</td><td style="padding:7px 0;text-align:right;border-top:1px solid #f2eadd">{{ $order->receiver_name }} · {{ $order->receiver_phone }}</td></tr>
            <tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">배송지</td><td style="padding:7px 0;text-align:right;border-top:1px solid #f2eadd">({{ $order->postcode }}) {{ $order->address1 }} {{ $order->address2 }}</td></tr>
            @if($order->memo)<tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">요청사항</td><td style="padding:7px 0;text-align:right;border-top:1px solid #f2eadd">{{ $order->memo }}</td></tr>@endif
        </table>

        <table style="width:100%;border-collapse:collapse;font-size:13px;margin:0 0 16px">
            <thead><tr style="background:#fff8ef">
                <th align="left" style="padding:8px 10px;border-bottom:2px solid #ecdcc4">상품</th>
                <th align="center" style="padding:8px 10px;border-bottom:2px solid #ecdcc4">수량</th>
                <th align="right" style="padding:8px 10px;border-bottom:2px solid #ecdcc4">금액</th>
            </tr></thead>
            <tbody>
            @foreach($order->items as $it)
                <tr>
                    <td style="padding:8px 10px;border-bottom:1px solid #f2eadd">{{ $it->product_name }}</td>
                    <td align="center" style="padding:8px 10px;border-bottom:1px solid #f2eadd">{{ $it->quantity }}</td>
                    <td align="right" style="padding:8px 10px;border-bottom:1px solid #f2eadd">{{ number_format($it->subtotal) }}원</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr>
                <td colspan="2" align="right" style="padding:10px;font-weight:700">합계</td>
                <td align="right" style="padding:10px;font-size:16px;font-weight:800;color:#e8730a">{{ number_format($order->total) }}원</td>
            </tr></tfoot>
        </table>

        <div style="text-align:center;margin:22px 0 8px">
            <a href="{{ $manageUrl }}" style="display:inline-block;background:#ff6b00;color:#fff;font-weight:800;font-size:15px;text-decoration:none;padding:14px 34px;border-radius:30px">주문 확인 · 배송처리 →</a>
        </div>
        <p style="font-size:12px;color:#a99a80;text-align:center;margin:6px 0 0">버튼이 열리지 않으면 다음 주소를 브라우저에 붙여넣으세요:<br><span style="word-break:break-all">{{ $manageUrl }}</span></p>

        <hr style="border:0;border-top:1px solid #f0e6d6;margin:20px 0">
        <div style="font-size:12px;color:#a99a80;line-height:1.8">
            <b>{{ $site['company'] ?? '망고샵' }}</b> · 고객센터 {{ $site['cs_tel'] ?? '' }} · {{ $site['email'] ?? '' }}
        </div>
    </div>
</div>
