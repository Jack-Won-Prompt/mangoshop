@extends('layouts.app')
@section('title', '회원 탈퇴 — 망고샵')

@section('content')
<div class="page-head"><div class="container"><h1>회원 탈퇴</h1></div></div>

<div class="container" style="max-width:640px;padding:26px 20px 60px">
    <div class="form-card" style="padding:28px 30px">
        <div style="background:var(--red-50);border:1px solid #f6cfcf;border-radius:12px;padding:16px 18px;margin-bottom:22px">
            <b style="color:var(--red);font-size:15px">탈퇴 전 꼭 확인해 주세요</b>
            <ul style="margin:10px 0 0;padding-left:18px;font-size:13.5px;color:var(--slate-600);line-height:1.9">
                <li>탈퇴 시 계정이 비활성화되고 <b>이름·연락처·주소 등 개인정보가 즉시 파기</b>됩니다.</li>
                <li>보유 <b>적립금·쿠폰은 소멸</b>되며 복구되지 않습니다.</li>
                <li>장바구니·관심상품·배송지·계약가 정보가 삭제됩니다.</li>
                <li>전자상거래법에 따라 <b>주문·결제 기록은 5년간 보관</b>(개인정보는 익명화)됩니다.</li>
                <li>동일 이메일로 재가입이 제한될 수 있습니다.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('mypage.withdraw.submit') }}">
            @csrf
            @method('DELETE')

            <div class="field">
                <label>비밀번호 확인 <span class="req">*</span></label>
                <input type="password" name="password" class="input" required placeholder="현재 비밀번호를 입력하세요">
                @error('password')<p class="muted" style="color:var(--red);font-size:12.5px;margin-top:6px">{{ $message }}</p>@enderror
            </div>

            <label style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;margin:14px 0 20px;cursor:pointer">
                <input type="checkbox" name="agree" value="1" required style="width:16px;height:16px">
                위 안내를 모두 확인했으며, 탈퇴에 동의합니다.
            </label>

            <div style="display:flex;gap:10px">
                <a href="{{ route('mypage.profile') }}" class="btn btn-ghost btn-lg" style="flex:1">취소</a>
                <button type="submit" class="btn btn-red btn-lg" style="flex:1" onclick="return confirm('정말 탈퇴하시겠습니까? 되돌릴 수 없습니다.')">회원 탈퇴</button>
            </div>
        </form>
    </div>
</div>
@endsection
