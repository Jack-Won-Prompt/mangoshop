@extends('layouts.app')
@section('title', '개인정보처리방침 — 망고샵')
@section('desc', '망고샵 개인정보처리방침 — 수집 항목, 이용 목적, 보관 기간, 제3자 제공 및 이용자 권리 안내')

@section('content')
<div class="page-head"><div class="container"><h1>개인정보처리방침</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:900px">

    <p class="muted" style="font-size:14px;line-height:1.7">
        {{ $site['company'] ?? '망고샵' }}(이하 ‘회사’)은 이용자의 개인정보를 중요하게 생각하며,
        「개인정보 보호법」 및 「정보통신망 이용촉진 및 정보보호 등에 관한 법률」을 준수합니다.
        본 방침은 망고샵 웹사이트 및 모바일 앱(이하 ‘서비스’)에 공통 적용됩니다.
    </p>
    <p class="muted" style="font-size:13px;margin-top:6px">시행일: 2026-07-29</p>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 12px">1. 수집하는 개인정보 항목</h3>
    <table class="info-table" style="width:100%;border-collapse:collapse;font-size:14px">
        <tbody>
            <tr><th style="width:150px;text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">회원가입</th><td style="padding:12px 16px;border:1px solid var(--line)">이름, 이메일, 비밀번호(암호화 저장), 휴대전화번호, 회원구분(도매/소매)</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">도매(사업자) 인증</th><td style="padding:12px 16px;border:1px solid var(--line)">상호, 사업자등록번호, 업태·종목, 사업자등록증(제출 시)</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">주문·배송</th><td style="padding:12px 16px;border:1px solid var(--line)">수령인 이름·연락처, 배송지 주소, 주문내역, 배송 요청사항</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">결제</th><td style="padding:12px 16px;border:1px solid var(--line)">결제수단·결제내역 (카드번호 등 결제정보는 결제대행사(PG)가 처리하며 회사는 저장하지 않습니다)</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">자동 수집</th><td style="padding:12px 16px;border:1px solid var(--line)">기기 푸시 알림 토큰(FCM), 접속 로그·IP·기기정보, 서비스 이용기록</td></tr>
        </tbody>
    </table>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 12px">2. 개인정보의 이용 목적</h3>
    <ul style="font-size:14px;line-height:1.9;padding-left:20px">
        <li>회원 식별 및 관리, 서비스 부정이용 방지</li>
        <li>상품 주문·결제·배송 및 정산</li>
        <li>도매 회원 사업자 인증 및 등급별 가격(도매가) 적용</li>
        <li>고객 상담·문의 응대, 공지·주문상태 등 알림(푸시) 발송</li>
        <li>관련 법령에 따른 의무 이행 및 분쟁 대응</li>
    </ul>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 12px">3. 보유 및 이용 기간</h3>
    <p style="font-size:14px;line-height:1.7">
        원칙적으로 개인정보는 <b>회원 탈퇴 시 지체 없이 파기</b>합니다. 다만 관계 법령에 따라 아래 정보는 명시된 기간 동안 보관합니다.
    </p>
    <table class="info-table" style="width:100%;border-collapse:collapse;font-size:14px;margin-top:10px">
        <tbody>
            <tr><th style="width:60%;text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">계약 또는 청약철회 등에 관한 기록</th><td style="padding:12px 16px;border:1px solid var(--line)">5년 (전자상거래법)</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">대금결제 및 재화 등의 공급에 관한 기록</th><td style="padding:12px 16px;border:1px solid var(--line)">5년 (전자상거래법)</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">소비자 불만 또는 분쟁처리에 관한 기록</th><td style="padding:12px 16px;border:1px solid var(--line)">3년 (전자상거래법)</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">접속 로그 기록</th><td style="padding:12px 16px;border:1px solid var(--line)">3개월 (통신비밀보호법)</td></tr>
        </tbody>
    </table>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 12px">4. 제3자 제공 및 처리위탁</h3>
    <p style="font-size:14px;line-height:1.7">회사는 원칙적으로 이용자의 개인정보를 외부에 제공하지 않으며, 원활한 서비스 제공을 위해 아래와 같이 업무를 위탁합니다.</p>
    <table class="info-table" style="width:100%;border-collapse:collapse;font-size:14px;margin-top:10px">
        <tbody>
            <tr><th style="width:220px;text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">결제대행사(PG)</th><td style="padding:12px 16px;border:1px solid var(--line)">신용카드·간편결제·계좌이체 등 결제 처리</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">택배·물류사</th><td style="padding:12px 16px;border:1px solid var(--line)">주문 상품 배송 (수령인·연락처·주소)</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">입점 수입사(판매자)</th><td style="padding:12px 16px;border:1px solid var(--line)">주문 이행에 필요한 배송정보 제공 (멀티벤더 특성상 주문은 수입사 단위로 처리)</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">Google (FCM)</th><td style="padding:12px 16px;border:1px solid var(--line)">푸시 알림 발송</td></tr>
        </tbody>
    </table>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 12px">5. 이용자의 권리와 행사 방법</h3>
    <p style="font-size:14px;line-height:1.7">
        이용자는 언제든지 자신의 개인정보를 조회·수정할 수 있으며, 회원 탈퇴 및 개인정보 삭제를 요청할 수 있습니다.
        앱 <b>마이페이지 → 회원정보 수정</b>에서 직접 수정하거나, <a href="{{ route('legal.account-deletion') }}" style="color:var(--navy-700);font-weight:700">계정 및 데이터 삭제 요청</a> 페이지의 절차를 따라 삭제를 요청할 수 있습니다.
    </p>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 12px">6. 개인정보 보호책임자</h3>
    <table class="info-table" style="width:100%;border-collapse:collapse;font-size:14px">
        <tbody>
            <tr><th style="width:150px;text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">상호</th><td style="padding:12px 16px;border:1px solid var(--line)">{{ $site['company'] ?? '망고샵' }}</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">이메일</th><td style="padding:12px 16px;border:1px solid var(--line)">{{ $site['email'] ?? 'help@mangoshop.co.kr' }}</td></tr>
            <tr><th style="text-align:left;padding:12px 16px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">고객센터</th><td style="padding:12px 16px;border:1px solid var(--line)">{{ $site['cs_tel'] ?? '' }} ({{ $site['cs_hours'] ?? '' }})</td></tr>
        </tbody>
    </table>

    <p class="muted" style="font-size:13px;line-height:1.7;margin-top:26px">
        본 개인정보처리방침은 법령·서비스 변경에 따라 개정될 수 있으며, 개정 시 서비스 내 공지를 통해 안내합니다.
    </p>
</div>
@endsection
