@extends('layouts.app')
@section('title', '이용약관 — 망고샵')
@section('desc', '망고샵 서비스 이용약관')

@section('content')
<div class="page-head"><div class="container"><h1>이용약관</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:900px">
    <p class="muted" style="font-size:13px;margin-bottom:18px">시행일: 2026-07-29</p>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제1조 (목적)</h3>
    <p style="font-size:14px;line-height:1.75">
        본 약관은 {{ $site['company'] ?? '망고샵' }}(이하 ‘회사’)이 운영하는 수입 과일 오픈마켓 ‘망고샵’(웹·모바일 앱, 이하 ‘서비스’)의
        이용과 관련하여 회사와 이용자 간의 권리·의무 및 책임사항을 규정함을 목적으로 합니다.
    </p>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제2조 (정의)</h3>
    <ul style="font-size:14px;line-height:1.9;padding-left:20px">
        <li>‘회원’이란 본 약관에 동의하고 서비스 이용 계약을 체결한 자를 말합니다.</li>
        <li>‘도매회원’이란 사업자 인증 및 승인을 받아 도매가로 구매할 수 있는 회원을 말합니다.</li>
        <li>‘소매회원’이란 별도 승인 없이 정가로 구매하는 회원을 말합니다.</li>
        <li>‘수입사(판매자)’란 서비스에 입점하여 상품을 등록·판매하는 사업자를 말합니다.</li>
    </ul>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제3조 (약관의 효력 및 변경)</h3>
    <p style="font-size:14px;line-height:1.75">
        본 약관은 서비스 화면에 게시함으로써 효력이 발생합니다. 회사는 관련 법령을 위배하지 않는 범위에서 약관을 개정할 수 있으며,
        개정 시 적용일자 및 사유를 명시하여 사전 공지합니다.
    </p>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제4조 (회원가입 및 도매 인증)</h3>
    <p style="font-size:14px;line-height:1.75">
        이용자는 회사가 정한 절차에 따라 회원가입을 신청하며, 도매가 이용을 원하는 경우 사업자 정보를 제출하고 회사의 승인을 받아야 합니다.
        회사는 허위 정보 제출, 명의도용 등이 확인될 경우 이용을 제한하거나 계약을 해지할 수 있습니다.
    </p>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제5조 (주문 및 결제)</h3>
    <p style="font-size:14px;line-height:1.75">
        회원은 서비스에 게시된 방법으로 상품을 주문하고 결제합니다. 멀티벤더 특성상 하나의 주문은 수입사 단위로 분리되어 처리될 수 있습니다.
        상품 가격·재고·배송정책은 각 수입사 및 상품 상세에 표시된 바에 따릅니다.
    </p>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제6조 (청약철회 및 반품)</h3>
    <p style="font-size:14px;line-height:1.75">
        회원은 관계 법령에 따라 청약철회를 할 수 있습니다. 다만 신선식품의 특성상 <b>수령 후 단순 변심에 의한 반품</b>이 제한될 수 있으며,
        상품 하자·오배송의 경우 회사 및 수입사가 신속히 교환·환불 처리합니다. 자세한 사항은 고객센터로 문의해 주세요.
    </p>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제7조 (회원 탈퇴 및 이용제한)</h3>
    <p style="font-size:14px;line-height:1.75">
        회원은 언제든지 탈퇴를 요청할 수 있으며, 절차는 <a href="{{ route('legal.account-deletion') }}" style="color:var(--navy-700);font-weight:700">계정 및 데이터 삭제 요청</a> 페이지를 따릅니다.
        회사는 회원이 약관·법령을 위반한 경우 서비스 이용을 제한할 수 있습니다.
    </p>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제8조 (개인정보 보호)</h3>
    <p style="font-size:14px;line-height:1.75">
        회사는 이용자의 개인정보를 <a href="{{ route('legal.privacy') }}" style="color:var(--navy-700);font-weight:700">개인정보처리방침</a>에 따라 보호합니다.
    </p>

    <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin:24px 0 10px">제9조 (책임의 한계)</h3>
    <p style="font-size:14px;line-height:1.75">
        회사는 천재지변, 이용자의 귀책사유로 인한 서비스 이용 장애에 대해 책임을 지지 않습니다.
        상품에 관한 정보·품질의 1차적 책임은 해당 상품을 등록·판매한 수입사에 있으며, 회사는 통신판매중개자로서의 지위를 겸할 수 있습니다.
    </p>

    <p class="muted" style="font-size:13px;line-height:1.7;margin-top:26px">
        본 약관에 명시되지 않은 사항은 관계 법령 및 상관례에 따릅니다.
    </p>
</div>
@endsection
