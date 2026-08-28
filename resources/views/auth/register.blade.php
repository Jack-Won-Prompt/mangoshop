@extends('layouts.app')
@section('title', '회원가입 — 망고샵')

@section('content')
<div class="auth-wrap" style="max-width:560px">
    <div class="auth-card">
        <a href="{{ route('home') }}" class="brand" style="justify-content:center"><img src="{{ asset('images/logo.svg') }}" alt="망고샵" class="brand-logo" style="height:46px"></a>
        <h2>회원가입</h2>
        <p class="sub">가입 즉시 {{ number_format($site['signup_point']) }}원 적립금 지급</p>

        <form method="POST" action="{{ route('register.attempt') }}">
            @csrf
            <div class="field" data-radio-cards>
                <label>회원 구분</label>
                <div class="radio-cards">
                    <label class="radio-card {{ old('member_type','general')==='general' ? 'on' : '' }}">
                        <input type="radio" name="member_type" value="general" hidden {{ old('member_type','general')==='general' ? 'checked' : '' }}>
                        <strong>일반 회원</strong><small>개인 구매자 · 정가 구매</small>
                    </label>
                    <label class="radio-card {{ old('member_type')==='business' ? 'on' : '' }}">
                        <input type="radio" name="member_type" value="business" hidden {{ old('member_type')==='business' ? 'checked' : '' }}>
                        <strong>도매 회원</strong><small>승인 후 도매 전용가</small>
                    </label>
                </div>
            </div>

            <div class="row2">
                <div class="field"><label>이름 <span class="req">*</span></label><input type="text" name="name" class="input" value="{{ old('name') }}" required></div>
                <div class="field"><label>연락처</label><input type="text" name="phone" class="input" value="{{ old('phone') }}"></div>
            </div>
            <div class="field"><label>이메일 <span class="req">*</span></label><input type="email" name="email" class="input" value="{{ old('email') }}" required></div>
            <div class="row2">
                <div class="field"><label>비밀번호 <span class="req">*</span></label><input type="password" name="password" class="input" required></div>
                <div class="field"><label>비밀번호 확인 <span class="req">*</span></label><input type="password" name="password_confirmation" class="input" required></div>
            </div>

            {{-- 사업자 전용 --}}
            <div id="biz-fields" style="display:none;background:var(--navy-50);border:1px solid var(--navy-100);border-radius:10px;padding:16px;margin-bottom:16px">
                <div style="font-size:13px;font-weight:700;color:var(--navy-800);margin-bottom:12px">사업자 정보 (승인용)</div>
                <div class="field"><label>상호(업체명)</label><input type="text" name="company_name" class="input" value="{{ old('company_name') }}"></div>
                <div class="row2">
                    <div class="field"><label>사업자등록번호</label><input type="text" name="biz_no" class="input" value="{{ old('biz_no') }}" placeholder="000-00-00000"></div>
                    <div class="field"><label>업태/종별</label><input type="text" name="biz_type" class="input" value="{{ old('biz_type') }}" placeholder="예: 의원"></div>
                </div>
                <p class="muted" style="font-size:12px;margin:0">※ 관리자 확인 후 승인되며, 승인 시 도매 전용가가 적용됩니다.</p>
            </div>

            {{-- ===== 약관·개인정보·마케팅 동의 ===== --}}
            <div class="agree-box">
                <label class="agree-all"><input type="checkbox" id="agreeAll"> <b>전체 동의</b> <span class="muted">(선택 항목 포함)</span></label>
                <div class="agree-list">
                    <div class="agree-item">
                        <label><input type="checkbox" name="agree_terms" value="1" class="agree-req" {{ old('agree_terms') ? 'checked' : '' }} required> <b>[필수]</b> 이용약관 동의</label>
                        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="agree-view">보기</a>
                    </div>
                    <div class="agree-item">
                        <label><input type="checkbox" name="agree_privacy" value="1" class="agree-req" {{ old('agree_privacy') ? 'checked' : '' }} required> <b>[필수]</b> 개인정보 수집·이용 동의</label>
                        <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="agree-view">보기</a>
                    </div>
                    <details class="agree-detail">
                        <summary>개인정보 수집·이용 안내 펼쳐보기</summary>
                        <div class="agree-content">
                            · <b>수집 항목</b> : 이름, 이메일, 비밀번호, 연락처(선택), 사업자회원은 상호·사업자등록번호·업태<br>
                            · <b>수집·이용 목적</b> : 회원 식별·관리, 주문·결제·배송, 고객문의 응대, 도매회원 승인<br>
                            · <b>보유·이용 기간</b> : 회원 탈퇴 시까지(관계 법령에 따른 거래·결제기록은 법정 보관기간 동안 보관)<br>
                            · 동의를 거부할 권리가 있으나, 필수 항목 미동의 시 회원가입이 제한됩니다.<br>
                            <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">개인정보처리방침 전문 보기 →</a>
                        </div>
                    </details>
                    <div class="agree-item">
                        <label><input type="checkbox" name="agree_marketing" value="1" class="agree-opt" {{ old('agree_marketing') ? 'checked' : '' }}> <b>[선택]</b> 마케팅 정보 수신 동의</label>
                    </div>
                    <details class="agree-detail">
                        <summary>마케팅 수신 동의 안내 펼쳐보기</summary>
                        <div class="agree-content">
                            · <b>수신 내용</b> : 신상품·할인·이벤트·혜택 등 마케팅 정보<br>
                            · <b>수신 채널</b> : 이메일, 문자(SMS/알림톡)<br>
                            · 동의하지 않아도 회원가입 및 서비스 이용이 가능하며, 동의 후에도 마이페이지에서 언제든 철회할 수 있습니다.
                        </div>
                    </details>
                </div>
            </div>
            <button class="btn btn-primary btn-lg btn-block">가입하기</button>
        </form>
        <div class="auth-links"><span>이미 회원이신가요?</span><a href="{{ route('login') }}">로그인</a></div>
    </div>
</div>

<style>
    .agree-box{border:1px solid var(--slate-200,#e3e8f1);border-radius:12px;padding:14px 16px;margin-bottom:16px;font-size:13.5px}
    .agree-all{display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:1px solid #eef1f6;margin-bottom:10px;cursor:pointer}
    .agree-list{display:flex;flex-direction:column;gap:8px}
    .agree-item{display:flex;align-items:center;justify-content:space-between}
    .agree-item label{display:flex;align-items:center;gap:8px;cursor:pointer;margin:0}
    .agree-item b{color:var(--primary,#ff6b00);font-weight:700}
    .agree-view{color:#8a93a8;font-size:12px;text-decoration:underline;white-space:nowrap;margin-left:8px}
    .agree-detail{background:#f8fafc;border-radius:8px}
    .agree-detail summary{cursor:pointer;font-size:12px;color:#6b7794;padding:8px 10px;list-style:none}
    .agree-detail summary::-webkit-details-marker{display:none}
    .agree-detail summary::before{content:'▾ '}
    .agree-content{font-size:12px;color:#5a6577;line-height:1.9;padding:0 12px 12px}
    .agree-content a{color:var(--primary,#ff6b00);font-weight:600}
</style>
<script>
(function(){
    var all=document.getElementById('agreeAll');
    if(!all)return;
    var boxes=document.querySelectorAll('.agree-req,.agree-opt');
    function syncAll(){ all.checked=Array.prototype.every.call(boxes,function(x){return x.checked;}); }
    all.addEventListener('change',function(){ boxes.forEach(function(b){ b.checked=all.checked; }); });
    boxes.forEach(function(b){ b.addEventListener('change',syncAll); });
    syncAll();
})();
</script>
@endsection
