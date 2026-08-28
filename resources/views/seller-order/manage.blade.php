@php($site = config('site'))
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>주문 확인 · {{ $order->order_no }} · {{ $site['name'] ?? '망고샵' }}</title>
<style>
    *{box-sizing:border-box}
    body{margin:0;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;background:#fbf7f1;color:#2b2113;line-height:1.6}
    .wrap{max-width:640px;margin:0 auto;padding:20px 16px 60px}
    .top{background:linear-gradient(135deg,#ff8a1e,#ffb347);color:#fff;border-radius:16px;padding:22px 24px;margin-bottom:18px}
    .top h1{margin:0;font-size:20px;font-weight:800}
    .top .no{opacity:.95;margin-top:4px;font-size:14px}
    .card{background:#fff;border:1px solid #f0e6d6;border-radius:14px;padding:20px 22px;margin-bottom:16px}
    .card h2{font-size:15px;margin:0 0 12px;font-weight:800}
    .row{display:flex;justify-content:space-between;font-size:13.5px;padding:6px 0;border-top:1px solid #f5efe6}
    .row:first-child{border-top:0}
    .row .k{color:#8a7a5f}
    table{width:100%;border-collapse:collapse;font-size:13.5px}
    th,td{padding:9px 8px;border-bottom:1px solid #f2eadd;text-align:left}
    th{background:#fff8ef}
    .tot{font-size:17px;font-weight:800;color:#e8730a;text-align:right;padding-top:12px}
    label{display:block;font-weight:700;font-size:13px;margin:12px 0 6px}
    input,select{width:100%;padding:11px 12px;border:1px solid #e3d8c6;border-radius:9px;font-size:14px;font-family:inherit;background:#fff}
    .btn{width:100%;margin-top:18px;background:#ff6b00;color:#fff;border:0;border-radius:30px;padding:14px;font-size:15px;font-weight:800;cursor:pointer}
    .ok{background:#eafaf0;border:1px solid #b7e6c9;color:#16794a;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13.5px}
    .pill{display:inline-block;font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;background:#fff1e0;color:#c9640a}
    .shipped{background:#e7f0ff;color:#2158c9}
    .muted{color:#a99a80;font-size:12px;text-align:center;margin-top:18px}
</style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>🥭 주문 확인 · 배송처리</h1>
        <div class="no">{{ $order->order_no }} · {{ optional($order->paid_at ?: $order->created_at)->format('Y.m.d H:i') }}
            @if($order->seller) · {{ $order->seller->name }}@endif</div>
    </div>

    @if(session('ok'))<div class="ok">✓ {{ session('ok') }}</div>@endif

    <div class="card">
        <h2>주문 상품</h2>
        <table>
            <thead><tr><th>상품</th><th style="text-align:center;width:60px">수량</th><th style="text-align:right;width:100px">금액</th></tr></thead>
            <tbody>
            @foreach($order->items as $it)
                <tr><td>{{ $it->product_name }}</td><td style="text-align:center">{{ $it->quantity }}</td><td style="text-align:right">{{ number_format($it->subtotal) }}원</td></tr>
            @endforeach
            </tbody>
        </table>
        <div class="tot">합계 {{ number_format($order->total) }}원</div>
    </div>

    <div class="card">
        <h2>배송지</h2>
        <div class="row"><span class="k">받는분</span><span>{{ $order->receiver_name }} · {{ $order->receiver_phone }}</span></div>
        <div class="row"><span class="k">주소</span><span style="text-align:right">({{ $order->postcode }}) {{ $order->address1 }} {{ $order->address2 }}</span></div>
        @if($order->memo)<div class="row"><span class="k">요청사항</span><span style="text-align:right">{{ $order->memo }}</span></div>@endif
        <div class="row"><span class="k">현재 상태</span><span class="pill {{ $order->status==='shipped'?'shipped':'' }}">{{ $order->statusLabel() }}</span></div>
    </div>

    <div class="card">
        <h2>배송예정일 확정 · 송장 등록</h2>
        <p style="font-size:13px;color:#8a7a5f;margin:0 0 4px">입력 후 저장하면 <b>구매자에게 발송 안내 문자(SMS)</b>가 전송됩니다.</p>
        <form method="POST" action="{{ $shipUrl }}">
            @csrf
            <label>배송예정일</label>
            <input type="date" name="delivery_date" value="{{ optional($order->desired_delivery_date)->format('Y-m-d') }}">
            <label>택배사</label>
            <select name="courier">
                @foreach(['CJ대한통운','한진택배','롯데택배','우체국택배','로젠택배','쿠팡'] as $c)
                    <option value="{{ $c }}" {{ $order->courier===$c?'selected':'' }}>{{ $c }}</option>
                @endforeach
            </select>
            <label>송장번호</label>
            <input type="text" name="tracking_no" value="{{ $order->tracking_no }}" placeholder="숫자만 입력" required>
            <button class="btn" type="submit">{{ $order->tracking_no ? '배송정보 수정 · 문자 재전송' : '배송 확정 · 구매자에게 문자 전송' }}</button>
        </form>
    </div>

    <div class="muted">{{ $site['company'] ?? '망고샵' }} · 고객센터 {{ $site['cs_tel'] ?? '' }}</div>
</div>
</body>
</html>
