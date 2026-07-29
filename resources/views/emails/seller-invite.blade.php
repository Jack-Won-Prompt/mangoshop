<!DOCTYPE html>
<html lang="ko">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f6f7f9;font-family:'Malgun Gothic','Apple SD Gothic Neo',Arial,sans-serif;color:#222">
    <div style="max-width:560px;margin:0 auto;padding:28px 16px">
        <div style="text-align:center;padding:8px 0 22px">
            <span style="font-size:26px;font-weight:900;color:#ff6b00">🥭 {{ $siteName }}</span>
        </div>
        <div style="background:#fff;border:1px solid #ececef;border-radius:16px;overflow:hidden">
            <div style="background:linear-gradient(120deg,#ffd9a6,#ffe9cf);padding:28px 30px">
                <div style="font-size:12px;font-weight:800;letter-spacing:1px;color:#e85d00">SELLER INVITATION</div>
                <div style="font-size:22px;font-weight:900;color:#3d2a12;margin-top:6px;line-height:1.35">수입사 입점에 초대합니다</div>
            </div>
            <div style="padding:28px 30px">
                <p style="font-size:15px;line-height:1.7;margin:0 0 16px">
                    안녕하세요,@if($invite->company_name) <b>{{ $invite->company_name }}</b>@endif 담당자님.<br>
                    <b>{{ $siteName }}</b> 수입 과일 오픈마켓에 입점 파트너로 모시고자 초대장을 보내드립니다.
                </p>
                @if($invite->origin_focus)
                    <p style="font-size:14px;color:#555;margin:0 0 16px">주력 원산지: <b style="color:#0f7a37">{{ $invite->origin_focus }}</b></p>
                @endif
                <p style="font-size:14px;color:#555;line-height:1.7;margin:0 0 22px">
                    아래 버튼을 눌러 입점 신청을 완료하시면 전용 스토어가 개설되고
                    상품 등록·주문·정산을 이용하실 수 있습니다.
                </p>
                <div style="text-align:center;margin:0 0 22px">
                    <a href="{{ $acceptUrl }}" style="display:inline-block;background:#ff6b00;color:#fff;font-weight:800;font-size:15px;text-decoration:none;padding:14px 34px;border-radius:30px">입점 신청하기 →</a>
                </div>
                <p style="font-size:12.5px;color:#8a8f99;line-height:1.7;margin:0;border-top:1px solid #f0f0f2;padding-top:16px">
                    이 초대는 <b>{{ optional($invite->expires_at)->format('Y년 m월 d일') }}</b>까지 유효합니다.<br>
                    버튼이 동작하지 않으면 아래 주소를 브라우저에 붙여넣어 주세요:<br>
                    <span style="color:#999;word-break:break-all">{{ $acceptUrl }}</span>
                </p>
            </div>
        </div>
        <p style="text-align:center;font-size:11.5px;color:#aaa;margin:18px 0 0">
            © {{ date('Y') }} {{ $siteName }} · 본 메일은 입점 초대를 위해 발송되었습니다.
        </p>
    </div>
</body>
</html>
