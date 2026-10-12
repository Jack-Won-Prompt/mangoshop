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

    {{-- 메인 상품 섹션 순서/노출 --}}
    @php($secLabels = ['best' => '이달의 베스트 과일', 'new' => '새로 들어온 과일(선물세트)', 'deal' => '사업자 전용 도매 특가'])
    @php($curSections = collect($site['home_sections'] ?? [['key'=>'best','visible'=>true],['key'=>'new','visible'=>true],['key'=>'deal','visible'=>true]]))
    <div class="adm-card">
        <div class="h">메인 상품 섹션 순서 · 노출</div>
        <div style="padding:20px">
            <div class="ahint" style="margin-bottom:12px">메인페이지 상품 섹션의 <b>노출 순서</b>와 <b>표시/숨김</b>을 조정합니다. ▲▼로 순서를 바꾸고, 체크를 끄면 해당 섹션이 메인에서 숨겨집니다.</div>
            <div id="secList" style="display:grid;gap:8px">
                @foreach($curSections as $s)
                    <div class="sec-row" data-key="{{ $s['key'] }}" style="display:flex;align-items:center;gap:10px;border:1px solid #eef0f4;border-radius:9px;padding:10px 12px;background:#fff">
                        <span class="sec-handle" style="cursor:default;color:#c7cedd;font-weight:800">≡</span>
                        <label style="display:flex;align-items:center;gap:8px;flex:1;font-weight:600;color:#33415c;margin:0">
                            <input type="checkbox" name="section_visible[{{ $s['key'] }}]" value="1" @checked($s['visible'] ?? true) style="width:16px;height:16px">
                            {{ $secLabels[$s['key']] ?? $s['key'] }}
                        </label>
                        <input type="hidden" name="sections[]" value="{{ $s['key'] }}" class="sec-order">
                        <button type="button" class="abtn abtn-ghost abtn-sm sec-up" title="위로">▲</button>
                        <button type="button" class="abtn abtn-ghost abtn-sm sec-down" title="아래로">▼</button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 메인 혜택 안내 스트립 (배너 아래 4칸) --}}
    @php($strip = $site['home_strip'] ?? [])
    <div class="adm-card">
        <div class="h">메인 혜택 안내 스트립 (배너 아래)</div>
        <div style="padding:20px;display:grid;gap:14px">
            <div class="ahint">메인 배너 바로 아래 가로 4칸 안내 문구입니다. 아이콘·제목·설명을 수정할 수 있습니다.</div>
            @foreach($strip as $i => $s)
                <div style="display:grid;grid-template-columns:150px 1fr 1.4fr;gap:10px;align-items:end;border:1px solid #eef0f4;border-radius:8px;padding:12px">
                    <div class="afield" style="margin:0"><label>아이콘 키 {{ $i + 1 }}</label><input type="text" name="strip[{{ $i }}][icon]" class="ainput" maxlength="30" value="{{ old("strip.$i.icon", $s['icon'] ?? '') }}" placeholder="truck"></div>
                    <div class="afield" style="margin:0"><label>제목</label><input type="text" name="strip[{{ $i }}][title]" class="ainput" maxlength="40" value="{{ old("strip.$i.title", $s['title'] ?? '') }}" placeholder="예: 주문 후 순차 발송"></div>
                    <div class="afield" style="margin:0"><label>설명</label><input type="text" name="strip[{{ $i }}][desc]" class="ainput" maxlength="80" value="{{ old("strip.$i.desc", $s['desc'] ?? '') }}" placeholder="예: 입고 일정에 맞춰 출고"></div>
                </div>
            @endforeach
            <div class="ahint">아이콘 키 예: truck(배송), building(수입사), coin(할인), doc(문서), shield(안전), heart, star, check, tag, box, package, fire</div>
        </div>
    </div>

    {{-- 메인화면 피처 밴드 (적립·캐시백 / 수입·유통) --}}
    @php($feats = $site['home_features'] ?? [])
    @php($featLabels = ['① 적립·캐시백 영역 (좌측 상단, POINTS & CASHBACK)', '② 수입·유통 영역 (하단, IMPORT & DISTRIBUTION)'])
    <div class="adm-card">
        <div class="h">메인화면 피처 밴드 (텍스트·이미지)</div>
        <div style="padding:20px;display:grid;gap:24px">
            @foreach($feats as $i => $f)
                @php($fImg = old("feat.$i.image_path", $f['image'] ?? ''))
                <div style="border:1px solid #eef0f4;border-radius:10px;padding:16px;display:grid;gap:14px">
                    <div style="font-weight:700;color:#33415c">{{ $featLabels[$i] ?? ('피처 블록 '.($i + 1)) }}</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="afield"><label>소제목(영문 키커)</label><input type="text" name="feat[{{ $i }}][kicker]" class="ainput" maxlength="40" value="{{ old("feat.$i.kicker", $f['kicker'] ?? '') }}" placeholder="예: POINTS & CASHBACK"></div>
                        <div class="afield"><label>버튼 문구</label><input type="text" name="feat[{{ $i }}][button]" class="ainput" maxlength="30" value="{{ old("feat.$i.button", $f['button'] ?? '') }}" placeholder="예: 후기 쓰고 적립받기"></div>
                    </div>
                    <div class="afield">
                        <label>제목</label>
                        <input type="text" name="feat[{{ $i }}][title]" class="ainput" maxlength="200" value="{{ old("feat.$i.title", $f['title'] ?? '') }}" placeholder="예: 사고, 후기 쓰면&lt;br&gt;&lt;b&gt;적립금&lt;/b&gt;이 쌓입니다">
                        <div class="ahint">줄바꿈은 &lt;br&gt;, 강조는 &lt;b&gt;…&lt;/b&gt; 로 입력합니다. (그 외 태그는 자동 제거)</div>
                    </div>
                    <div class="afield"><label>설명 문구</label><textarea name="feat[{{ $i }}][desc]" class="ainput" rows="2" maxlength="400" placeholder="영역 설명 문구">{{ old("feat.$i.desc", $f['desc'] ?? '') }}</textarea></div>
                    <div class="afield"><label>태그 칩(쉼표로 구분)</label><input type="text" name="feat[{{ $i }}][chips]" class="ainput" maxlength="300" value="{{ old("feat.$i.chips", implode(', ', (array) ($f['chips'] ?? []))) }}" placeholder="예: 신규가입 3,000P, 구매 3% 적립, 상품후기 3% 적립"></div>
                    <div class="afield"><label>클릭 시 이동 주소</label><input type="text" name="feat[{{ $i }}][link]" class="ainput" maxlength="300" value="{{ old("feat.$i.link", $f['link'] ?? '') }}" placeholder="예: community/reviews 또는 https://…"></div>
                    <div class="afield">
                        <label>배경 이미지</label>
                        <div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap">
                            @if($fImg)
                                <img src="{{ preg_match('#^https?://#i', $fImg) ? $fImg : asset(ltrim($fImg, '/')) }}" alt="현재 이미지" style="width:140px;height:92px;object-fit:cover;border:1px solid #e5e7eb;border-radius:8px">
                            @endif
                            <div style="flex:1;min-width:240px">
                                <input type="file" name="feat[{{ $i }}][image]" accept="image/jpeg,image/png,image/webp,image/gif" class="ainput" style="padding:7px">
                                <input type="text" name="feat[{{ $i }}][image_path]" class="ainput" style="margin-top:8px" value="{{ $fImg }}" placeholder="images/main/cashback-points.jpg">
                                <div class="ahint">새 파일을 올리면 교체됩니다(JPG·PNG·WEBP·GIF, 5MB 이하, 권장 가로형 1200×800). 경로나 https 주소를 직접 입력할 수도 있습니다.</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 메인 팝업 → 전용 '팝업 관리' 메뉴로 이동 --}}
    <div class="adm-card">
        <div class="h">메인 팝업 (상품 홍보)</div>
        <div style="padding:20px">
            <div class="ahint" style="margin-bottom:12px">메인 팝업은 이제 <b>전시관리 → 메인 팝업</b> 메뉴에서 여러 개를 개별 등록·수정·노출 관리합니다.</div>
            <a href="{{ route('admin.index', 'popups') }}" class="abtn abtn-ghost"><x-icon name="monitor" :size="15"/> 팝업 관리로 이동</a>
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
    // 메인 섹션 순서 ▲▼
    var list = document.getElementById('secList');
    if (list) {
        list.addEventListener('click', function (e) {
            var up = e.target.closest('.sec-up'), down = e.target.closest('.sec-down');
            if (!up && !down) return;
            var row = e.target.closest('.sec-row');
            if (!row) return;
            if (up && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
            if (down && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
        });
    }
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
