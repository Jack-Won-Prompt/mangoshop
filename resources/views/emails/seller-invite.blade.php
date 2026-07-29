<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<title>{{ $siteName }} 수입사 입점 초대</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;-webkit-text-size-adjust:100%;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $siteName }} 수입 과일 오픈마켓 입점 파트너로 초대합니다.</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 12px;">
<tr><td align="center">

  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;font-family:'Apple SD Gothic Neo','Malgun Gothic',Roboto,'Helvetica Neue',Arial,sans-serif;">

    {{-- 헤더 --}}
    <tr><td style="background:linear-gradient(135deg,#ff7a18,#ff9d3c);border-radius:20px 20px 0 0;padding:34px 40px 30px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="font-size:23px;font-weight:800;color:#ffffff;letter-spacing:-.5px;">🥭 {{ $siteName }}</td>
        <td align="right" style="font-size:11px;font-weight:700;letter-spacing:2px;color:#fff3e4;">SELLER INVITATION</td>
      </tr></table>
    </td></tr>

    {{-- 본문 --}}
    <tr><td style="background:#ffffff;padding:44px 40px 12px;">
      <div style="font-size:13px;font-weight:800;letter-spacing:1px;color:#ff6b00;margin-bottom:12px;">입점 파트너 초대장</div>
      <div style="font-size:28px;line-height:1.32;font-weight:900;color:#1b1b1b;letter-spacing:-1px;">
        수입사 입점에<br>초대합니다
      </div>
      <p style="font-size:15px;line-height:1.75;color:#4a4a4a;margin:20px 0 0;">
        안녕하세요@if(!empty($invite->company_name)), <b style="color:#111;">{{ $invite->company_name }}</b> 담당자님@endif.<br>
        <b style="color:#ff6b00;">{{ $siteName }}</b> 수입 과일 오픈마켓에서 함께하실
        입점 파트너로 정중히 모시고자 초대장을 보내드립니다.
      </p>

      @if(!empty($invite->origin_focus))
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0 0;">
        <tr><td style="background:#f0f8f2;border:1px solid #cfe9d6;border-radius:12px;padding:14px 18px;font-size:14px;color:#0f7a37;">
          <b>주력 원산지</b> &nbsp;·&nbsp; {{ $invite->origin_focus }}
        </td></tr>
      </table>
      @endif
    </td></tr>

    {{-- 혜택 --}}
    <tr><td style="background:#ffffff;padding:26px 40px 4px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #f0f0f2;padding-top:24px;">
        @foreach([
          ['🏬','전용 스토어 개설','브랜드 스토어가 즉시 생성되어 소비자에게 노출됩니다.'],
          ['📦','상품·주문 관리','상품 등록부터 주문·배송·정산까지 한 곳에서.'],
          ['🚚','콜드체인 물류 지원','신선 배송·정산 프로세스를 함께 지원합니다.'],
        ] as $b)
        <tr>
          <td width="44" valign="top" style="padding:0 0 18px;"><div style="width:38px;height:38px;background:#fff4ea;border-radius:10px;text-align:center;line-height:38px;font-size:19px;">{{ $b[0] }}</div></td>
          <td valign="top" style="padding:0 0 18px 4px;">
            <div style="font-size:15px;font-weight:800;color:#1b1b1b;">{{ $b[1] }}</div>
            <div style="font-size:13.5px;color:#7a7a7a;margin-top:2px;line-height:1.5;">{{ $b[2] }}</div>
          </td>
        </tr>
        @endforeach
      </table>
    </td></tr>

    {{-- CTA --}}
    <tr><td style="background:#ffffff;padding:14px 40px 40px;" align="center">
      <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr><td style="border-radius:30px;background:#ff6b00;box-shadow:0 8px 22px rgba(255,107,0,.32);">
          <a href="{{ $acceptUrl }}" style="display:inline-block;padding:16px 46px;color:#ffffff;font-size:16px;font-weight:800;text-decoration:none;border-radius:30px;">입점 신청하기 &nbsp;→</a>
        </td></tr>
      </table>
      <p style="font-size:12.5px;color:#9a9a9a;line-height:1.7;margin:22px 0 0;">
        이 초대는 <b style="color:#555;">{{ optional($invite->expires_at)->format('Y년 m월 d일') }}</b>까지 유효합니다.<br>
        버튼이 열리지 않으면 아래 주소를 브라우저에 붙여넣어 주세요.
      </p>
      <p style="font-size:12px;color:#b0b0b0;word-break:break-all;margin:6px 0 0;">{{ $acceptUrl }}</p>
    </td></tr>

    {{-- 푸터 --}}
    <tr><td style="background:#fafafa;border-radius:0 0 20px 20px;border-top:1px solid #eee;padding:24px 40px;">
      <div style="font-size:14px;font-weight:900;color:#ff6b00;">{{ $siteName }}</div>
      <div style="font-size:11.5px;color:#9a9a9a;line-height:1.85;margin-top:8px;">
        @php($s = $site ?? [])
        상호 {{ $s['company'] ?? $siteName }} · 대표 {{ $s['ceo'] ?? '' }}@if(!empty($s['biz_no'])) · 사업자등록번호 {{ $s['biz_no'] }}@endif<br>
        @if(!empty($s['address'])){{ $s['address'] }}<br>@endif
        고객센터 {{ $s['cs_tel'] ?? '1600-0000' }}@if(!empty($s['email'])) · {{ $s['email'] }}@endif
      </div>
      <div style="font-size:11px;color:#c0c0c0;margin-top:12px;">본 메일은 입점 초대를 위해 발송되었습니다. © {{ date('Y') }} {{ $siteName }}</div>
    </td></tr>

  </table>

</td></tr>
</table>
</body>
</html>
