@extends('layouts.app')
@section('title', '계정 및 데이터 삭제 요청 — 망고샵')
@section('desc', '망고샵 계정 및 개인정보 삭제 요청 방법과 삭제·보관 데이터 안내')

@section('content')
<div class="page-head"><div class="container"><h1>계정 및 데이터 삭제 요청</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:860px">

    <div class="form-card" style="padding:24px 26px">
        <p style="font-size:15px;line-height:1.75">
            망고샵 회원은 언제든지 <b>계정 및 개인정보의 삭제</b>를 요청할 수 있습니다.
            아래 방법 중 하나로 요청하시면 본인 확인 후 처리해 드립니다.
        </p>
    </div>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:28px 0 12px">삭제 요청 방법</h3>
    <ol style="font-size:14.5px;line-height:1.95;padding-left:22px">
        <li>
            <b>이메일 요청</b> — <a href="mailto:{{ $site['email'] ?? 'help@mangoshop.co.kr' }}?subject=계정 삭제 요청" style="color:var(--navy-700);font-weight:700">{{ $site['email'] ?? 'help@mangoshop.co.kr' }}</a>
            으로 <b>가입한 이메일</b>과 함께 “계정 삭제 요청”을 보내주세요.
        </li>
        <li>
            <b>고객센터</b> — {{ $site['cs_tel'] ?? '' }} ({{ $site['cs_hours'] ?? '평일 09:00~18:00' }})
        </li>
        <li>
            <b>앱 내 문의</b> — 망고샵 앱 <b>마이페이지 → 1:1 문의</b>에서 삭제를 요청할 수 있습니다.
        </li>
    </ol>
    <p class="muted" style="font-size:13.5px;line-height:1.7;margin-top:6px">
        요청 접수 후 <b>영업일 기준 최대 7일 이내</b> 처리하며, 처리 완료 시 등록된 이메일로 안내드립니다.
        타인의 계정을 도용한 요청을 방지하기 위해 본인 확인 절차가 진행될 수 있습니다.
    </p>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:28px 0 12px">삭제되는 데이터</h3>
    <ul style="font-size:14.5px;line-height:1.9;padding-left:20px">
        <li>회원 계정 정보 — 이름, 이메일, 비밀번호, 휴대전화번호</li>
        <li>배송지 주소 및 프로필 정보</li>
        <li>도매 사업자 정보 — 상호, 사업자등록번호, 업태·종목</li>
        <li>관심상품, 장바구니, 작성한 후기·문의</li>
        <li>푸시 알림 토큰 및 접속 기록</li>
    </ul>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:28px 0 12px">법령에 따라 일정 기간 보관되는 데이터</h3>
    <p style="font-size:14px;line-height:1.7">
        전자상거래 등에서의 소비자보호에 관한 법률 등 관계 법령에 따라, 아래 정보는 삭제 요청과 무관하게 명시된 기간 동안 보관된 후 파기됩니다.
        (해당 정보는 보관 목적 외의 용도로 이용되지 않습니다.)
    </p>
    <table class="info-table" style="width:100%;border-collapse:collapse;font-size:14px;margin-top:10px">
        <tbody>
            <tr><th style="width:60%;text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">계약·청약철회 및 대금결제·재화공급 기록</th><td style="padding:12px 16px;border:1px solid var(--line)">5년</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">소비자 불만·분쟁처리 기록</th><td style="padding:12px 16px;border:1px solid var(--line)">3년</td></tr>
        </tbody>
    </table>

    <p class="muted" style="font-size:13.5px;line-height:1.7;margin-top:26px">
        자세한 내용은 <a href="{{ route('legal.privacy') }}" style="color:var(--navy-700);font-weight:700">개인정보처리방침</a>을 참고해 주세요.
    </p>
</div>
@endsection
