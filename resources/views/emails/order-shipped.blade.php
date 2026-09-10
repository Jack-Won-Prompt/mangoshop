@php($site = config('site'))
<div style="font-family:'Malgun Gothic','Apple SD Gothic Neo',sans-serif;font-size:14px;color:#2b2113;line-height:1.7;max-width:600px;margin:0 auto">
    <div style="background:linear-gradient(135deg,#0b3d91,#1f6feb);border-radius:14px 14px 0 0;padding:22px 24px;color:#fff">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.5px">🚚 {{ $site['name'] ?? '망고샵' }}</div>
        <div style="opacity:.92;margin-top:4px">주문하신 상품이 발송되었습니다</div>
    </div>
    <div style="border:1px solid #e2e8f4;border-top:0;border-radius:0 0 14px 14px;padding:24px">

        <table style="width:100%;border-collapse:collapse;font-size:14px;margin:0 0 16px">
            <tr><td style="padding:7px 0;color:#77839a;width:90px">주문번호</td><td style="padding:7px 0"><b>{{ $order->order_no }}</b></td></tr>
            <tr><td style="padding:7px 0;color:#77839a;border-top:1px solid #eef1f6">택배사</td><td style="padding:7px 0;border-top:1px solid #eef1f6"><b>{{ $order->courier }}</b></td></tr>
            <tr><td style="padding:7px 0;color:#77839a;border-top:1px solid #eef1f6">송장번호</td><td style="padding:7px 0;border-top:1px solid #eef1f6"><b style="font-size:16px;color:#0b3d91">{{ $order->tracking_no }}</b></td></tr>
        </table>

        <div style="text-align:center;margin:6px 0 18px">
            <a href="{{ $track }}" style="display:inline-block;background:#0b3d91;color:#fff;font-weight:700;font-size:15px;text-decoration:none;padding:13px 32px;border-radius:28px">📦 배송 조회하기 →</a>
        </div>

        <div style="background:#f6f8fc;border-radius:10px;padding:12px 15px;font-size:13px;color:#4b5563;margin-bottom:8px">
            @foreach($items as $it)
                {{ $it->display_name ?? $it->product_name }} × {{ $it->quantity }}<br>
            @endforeach
        </div>

        <hr style="border:0;border-top:1px solid #eef1f6;margin:18px 0">
        <div style="font-size:12px;color:#9aa3b2;line-height:1.8">
            <b>{{ $site['company'] ?? '망고샵' }}</b> · 고객센터 {{ $site['cs_tel'] ?? '' }}<br>
            배송 조회는 택배사 사정에 따라 등록 후 몇 시간 뒤부터 확인됩니다.
        </div>
    </div>
</div>
