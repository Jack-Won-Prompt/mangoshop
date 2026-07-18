<footer class="mg-footer">
    <div class="mg-foot-top">
        <div class="mg-wrap">
            <a href="{{ route('community.notices') }}">공지사항</a>
            <a href="{{ route('community.qna') }}">Q&amp;A</a>
            <a href="{{ route('community.reviews') }}">상품후기</a>
            <a href="{{ route('community.faq') }}">자주묻는질문</a>
            <a href="{{ route('mypage.orders') }}">배송조회</a>
            <a href="{{ route('community.qna') }}">회사소개</a>
            <a href="{{ route('community.qna') }}">이용약관</a>
            <a href="{{ route('community.qna') }}">개인정보처리방침</a>
        </div>
    </div>
    <div class="mg-foot-mid">
        <div class="mg-wrap">
            <div class="mg-foot-info">
                <div class="fname">🥭 망고샵</div>
                상호 : {{ $site['company'] ?? '망고샵' }} &nbsp;|&nbsp; 대표 : {{ $site['ceo'] ?? '' }}<br>
                사업자등록번호 : {{ $site['biz_no'] ?? '' }} &nbsp;|&nbsp; 통신판매업 신고 : {{ $site['mailorder'] ?? '' }}<br>
                주소 : {{ $site['address'] ?? '' }}<br>
                이메일 : {{ $site['email'] ?? '' }}<br>
                <span style="color:#aaa">여러 수입사가 입점하는 오픈마켓으로, 각 상품의 판매·배송·정산 책임은 해당 수입사에 있습니다.</span>
                <div style="color:#bbb;font-size:11.5px;margin-top:12px">© {{ date('Y') }} MANGOSHOP. All rights reserved.</div>
            </div>
            <div class="mg-foot-cs">
                <div style="font-size:13px;color:#999;font-weight:700">고객센터</div>
                <div class="tel">{{ $site['cs_tel'] ?? '1600-0000' }}</div>
                <div class="hours">{{ $site['cs_hours'] ?? '' }}</div>
                <div class="mg-foot-badges">
                    <span>공정거래위원회</span>
                    <span>에스크로 안전거래</span>
                    <span>콜드체인 배송</span>
                </div>
            </div>
        </div>
    </div>
</footer>
