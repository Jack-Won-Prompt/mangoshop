@extends('layouts.admin')
@section('title', '사이트 설정')
@section('heading', '사이트 설정')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" style="max-width:880px">
    @csrf @method('PUT')

    {{-- 기본 정보 --}}
    <div class="adm-card">
        <div class="h">기본 정보</div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>쇼핑몰명 <span style="color:#e0322d">*</span></label><input type="text" name="name" class="ainput" value="{{ old('name', $site['name'] ?? '') }}" required></div>
                <div class="afield"><label>영문명</label><input type="text" name="name_en" class="ainput" value="{{ old('name_en', $site['name_en'] ?? '') }}"></div>
            </div>
            <div class="afield"><label>슬로건</label><input type="text" name="tagline" class="ainput" value="{{ old('tagline', $site['tagline'] ?? '') }}"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>상호(법인명)</label><input type="text" name="company" class="ainput" value="{{ old('company', $site['company'] ?? '') }}"></div>
                <div class="afield"><label>대표자</label><input type="text" name="ceo" class="ainput" value="{{ old('ceo', $site['ceo'] ?? '') }}"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>사업자등록번호</label><input type="text" name="biz_no" class="ainput" value="{{ old('biz_no', $site['biz_no'] ?? '') }}"></div>
                <div class="afield"><label>통신판매업신고</label><input type="text" name="mailorder" class="ainput" value="{{ old('mailorder', $site['mailorder'] ?? '') }}"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>식품·농산물 인허가</label><input type="text" name="med_device" class="ainput" value="{{ old('med_device', $site['med_device'] ?? '') }}"></div>
                <div class="afield"><label>주소</label><input type="text" name="address" class="ainput" value="{{ old('address', $site['address'] ?? '') }}"></div>
            </div>
        </div>
    </div>

    {{-- 고객센터 --}}
    <div class="adm-card">
        <div class="h">고객센터</div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>대표전화</label><input type="text" name="cs_tel" class="ainput" value="{{ old('cs_tel', $site['cs_tel'] ?? '') }}"></div>
                <div class="afield"><label>이메일</label><input type="text" name="email" class="ainput" value="{{ old('email', $site['email'] ?? '') }}"></div>
            </div>
            <div class="afield"><label>운영시간 안내</label><input type="text" name="cs_hours" class="ainput" value="{{ old('cs_hours', $site['cs_hours'] ?? '') }}"></div>
        </div>
    </div>

    {{-- 무통장 입금계좌 --}}
    <div class="adm-card">
        <div class="h">무통장 입금계좌</div>
        <div style="padding:20px">
            <table class="atable" id="bankTable">
                <thead><tr><th style="width:160px">은행</th><th>계좌번호</th><th style="width:160px">예금주</th><th style="width:50px"></th></tr></thead>
                <tbody>
                    @php($banks = old('banks', $site['banks'] ?? []))
                    @forelse($banks as $i => $b)
                        <tr>
                            <td><input type="text" name="banks[{{ $i }}][bank]" class="ainput" value="{{ $b['bank'] ?? '' }}" placeholder="국민은행"></td>
                            <td><input type="text" name="banks[{{ $i }}][account]" class="ainput" value="{{ $b['account'] ?? '' }}" placeholder="000-00-000000"></td>
                            <td><input type="text" name="banks[{{ $i }}][holder]" class="ainput" value="{{ $b['holder'] ?? '' }}"></td>
                            <td><button type="button" class="abtn abtn-red abtn-sm" onclick="this.closest('tr').remove()">×</button></td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
            <button type="button" class="abtn abtn-ghost abtn-sm" id="addBank" style="margin-top:10px">＋ 계좌 추가</button>
        </div>
    </div>

    {{-- 결제 PG --}}
    <div class="adm-card">
        <div class="h">결제 PG (카드·간편·가상계좌)</div>
        <div style="padding:20px">
            <div class="afield" style="max-width:320px;margin:0">
                <label>사용할 PG</label>
                <select name="payment_pg" class="aselect">
                    <option value="toss" {{ ($site['payment_pg'] ?? 'toss')==='toss' ? 'selected' : '' }}>토스페이먼츠</option>
                    <option value="portone" {{ ($site['payment_pg'] ?? '')==='portone' ? 'selected' : '' }}>포트원(아임포트)</option>
                </select>
                <div class="ahint">체크아웃의 카드/간편결제가 선택한 PG로 진행됩니다. (무통장입금은 항상 제공)</div>
            </div>
        </div>
    </div>

    {{-- 배송 / 적립 정책 --}}
    <div class="adm-card">
        <div class="h">배송 / 적립 정책</div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>무료배송 기준금액(원)</label><input type="number" name="free_ship_over" class="ainput" value="{{ old('free_ship_over', $site['free_ship_over'] ?? 0) }}" required></div>
                <div class="afield"><label>기본 배송비(원)</label><input type="number" name="shipping_fee" class="ainput" value="{{ old('shipping_fee', $site['shipping_fee'] ?? 0) }}" required></div>
                <div class="afield"><label>신규가입 적립금(원)</label><input type="number" name="signup_point" class="ainput" value="{{ old('signup_point', $site['signup_point'] ?? 0) }}" required></div>
                <div class="afield"><label>구매 적립률(%)</label><input type="number" name="point_rate" class="ainput" value="{{ old('point_rate', $site['point_rate'] ?? 0) }}" required></div>
            </div>
        </div>
    </div>

    {{-- 메인화면 문구 --}}
    <div class="adm-card">
        <div class="h">메인화면 문구</div>
        <div style="padding:20px">
            <div class="afield">
                <label>‘새로 들어온 과일’ 섹션 제목</label>
                <input type="text" name="home_new_title" class="ainput" value="{{ old('home_new_title', $site['home_new_title'] ?? '새로 들어온 과일') }}" placeholder="예: 2026년 추석 명절 선물세트">
            </div>
            <div class="afield">
                <label>‘새로 들어온 과일’ 섹션 부제</label>
                <input type="text" name="home_new_sub" class="ainput" value="{{ old('home_new_sub', $site['home_new_sub'] ?? '') }}" placeholder="예: 명절 선물로 좋은 프리미엄 수입과일">
            </div>
            <div class="ahint">메인화면 상단 ‘새로 들어온 과일’ 영역의 제목·부제를 변경합니다.</div>
            <div class="afield" style="margin-top:14px">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
                    <input type="checkbox" name="home_show_recipe" value="1" style="width:16px;height:16px" @checked(old('home_show_recipe', $site['home_show_recipe'] ?? true))>
                    메인페이지 ‘최신 등록 레시피’ 영역 노출
                </label>
                <div class="ahint">체크 해제 시 메인페이지에서 레시피 커뮤니티 영역이 숨겨집니다.</div>
            </div>
        </div>
    </div>

    {{-- 메인 팝업 (상품 홍보) --}}
    @php($pp = $site['popup'] ?? [])
    @php($ppImg = old('popup_image_path', $pp['image'] ?? ''))
    <div class="adm-card" id="popup">
        <div class="h">메인 팝업 (상품 홍보)</div>
        <div style="padding:20px">
            <div class="afield">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
                    <input type="checkbox" name="popup_enabled" value="1" style="width:16px;height:16px" @checked(old('popup_enabled', $pp['enabled'] ?? false))>
                    메인페이지 접속 시 팝업 노출
                </label>
                <div class="ahint">방문자가 ‘오늘 하루 보지 않기’를 누르면 그날은 다시 뜨지 않습니다. 이미지·링크·문구·기간을 바꾸면 모든 방문자에게 다시 표시됩니다.</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>제목</label><input type="text" name="popup_title" class="ainput" maxlength="60" value="{{ old('popup_title', $pp['title'] ?? '') }}" placeholder="예: 추석 명절 선물세트"></div>
                <div class="afield"><label>부제</label><input type="text" name="popup_sub" class="ainput" maxlength="100" value="{{ old('popup_sub', $pp['sub'] ?? '') }}" placeholder="예: 프리미엄 애플망고 선물세트 · 8과/9과 선택"></div>
            </div>

            <div class="afield">
                <label>팝업 이미지</label>
                <div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap">
                    @if($ppImg)
                        <img src="{{ preg_match('#^https?://#i', $ppImg) ? $ppImg : asset(ltrim($ppImg, '/')) }}" alt="현재 팝업 이미지" style="width:120px;height:120px;object-fit:cover;border:1px solid #e5e7eb;border-radius:8px">
                    @endif
                    <div style="flex:1;min-width:240px">
                        <input type="file" name="popup_image" accept="image/jpeg,image/png,image/webp,image/gif" class="ainput" style="padding:7px">
                        <input type="text" name="popup_image_path" class="ainput" style="margin-top:8px" value="{{ $ppImg }}" placeholder="images/giftset/orchard/applemango-main.jpg">
                        <div class="ahint">새 파일을 올리면 교체됩니다(JPG·PNG·WEBP·GIF, 5MB 이하, 권장 정사각형 800px 이상). 아래 칸에 이미지 경로나 https 주소를 직접 입력할 수도 있습니다.</div>
                    </div>
                </div>
            </div>

            <div class="afield">
                <label>클릭 시 이동할 주소</label>
                <div style="display:grid;grid-template-columns:260px 1fr;gap:10px">
                    <select class="aselect" id="popupProductPick">
                        <option value="">상품에서 선택…</option>
                        @foreach($products as $p)
                            <option value="product/{{ $p->slug }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="popup_link" id="popupLink" class="ainput" maxlength="300" value="{{ old('popup_link', $pp['link'] ?? '') }}" placeholder="product/premium-apple-mango-giftset 또는 https://…">
                </div>
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                    <input type="checkbox" name="popup_new_window" value="1" style="width:16px;height:16px" @checked(old('popup_new_window', $pp['new_window'] ?? false))>
                    새 창으로 열기
                </label>
                <div class="ahint">비워두면 이미지 클릭 시 이동하지 않습니다.</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
                <div class="afield"><label>버튼 문구</label><input type="text" name="popup_button" class="ainput" maxlength="30" value="{{ old('popup_button', $pp['button'] ?? '') }}" placeholder="자세히 보기"></div>
                <div class="afield"><label>노출 시작일</label><input type="date" name="popup_start" class="ainput" value="{{ old('popup_start', $pp['start'] ?? '') }}"></div>
                <div class="afield"><label>노출 종료일</label><input type="date" name="popup_end" class="ainput" value="{{ old('popup_end', $pp['end'] ?? '') }}"></div>
            </div>
            <div class="ahint">노출 기간을 비워두면 기간 제한 없이 표시됩니다. 종료일 당일까지 노출됩니다.</div>
        </div>
    </div>

    {{-- 고객 문의 알림 이메일 --}}
    <div class="adm-card">
        <div class="h">고객 문의 알림 이메일</div>
        <div style="padding:20px">
            <div class="ahint" style="margin-bottom:10px">고객이 <b>문의하기</b>를 등록하면 아래 이메일로 문의 내용이 전송됩니다. (최대 3개)</div>
            @php($iemails = old('inquiry_emails', $site['inquiry_emails'] ?? []))
            @for($i = 0; $i < 3; $i++)
                <div class="afield"><label>알림 이메일 {{ $i + 1 }}</label>
                    <input type="email" name="inquiry_emails[]" class="ainput" value="{{ $iemails[$i] ?? '' }}" placeholder="예: cs@mangoshop.co.kr">
                </div>
            @endfor
        </div>
    </div>

    {{-- 인기검색어 --}}
    <div class="adm-card">
        <div class="h">인기 검색어</div>
        <div style="padding:20px">
            <div class="afield">
                <label>인기검색어 (쉼표로 구분)</label>
                <input type="text" name="popular_keywords" class="ainput" value="{{ old('popular_keywords', implode(', ', $site['popular_keywords'] ?? [])) }}" placeholder="주사기, 멸균거즈, 수액세트">
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px">
        <button class="abtn abtn-pri" style="padding:11px 28px">설정 저장</button>
    </div>
</form>

<script>
(function () {
    var pick = document.getElementById('popupProductPick');
    var link = document.getElementById('popupLink');
    pick.addEventListener('change', function () {
        if (pick.value) link.value = pick.value;
    });
})();
(function () {
    var add = document.getElementById('addBank');
    var tbody = document.querySelector('#bankTable tbody');
    add.addEventListener('click', function () {
        var i = tbody.children.length;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" name="banks['+i+'][bank]" class="ainput" placeholder="은행"></td>' +
            '<td><input type="text" name="banks['+i+'][account]" class="ainput" placeholder="계좌번호"></td>' +
            '<td><input type="text" name="banks['+i+'][holder]" class="ainput" placeholder="예금주"></td>' +
            '<td><button type="button" class="abtn abtn-red abtn-sm">×</button></td>';
        tr.querySelector('button').addEventListener('click', function(){ tr.remove(); });
        tbody.appendChild(tr);
    });
})();
</script>
@endsection
