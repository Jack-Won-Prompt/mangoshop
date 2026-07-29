@extends('layouts.app')
@section('title', '수입사 입점 신청 — 망고샵')

@section('content')
<div class="container" style="max-width:620px;padding:34px 20px 60px">

    @if(! $invite->isUsable())
        <div class="form-card" style="text-align:center;padding:44px 30px">
            <div style="font-size:56px">⌛</div>
            <h2 style="font-size:22px;font-weight:800;margin:12px 0 8px">사용할 수 없는 초대입니다</h2>
            <p class="muted" style="font-size:14.5px">
                @if($invite->status === 'accepted') 이미 입점 신청이 완료된 초대입니다.
                @elseif($invite->status === 'revoked') 취소된 초대입니다.
                @else 초대 유효기간이 만료되었습니다.
                @endif
                <br>자세한 사항은 고객센터로 문의해 주세요.
            </p>
            <a href="{{ url('/') }}" class="btn btn-primary" style="margin-top:18px">홈으로</a>
        </div>
    @else
        <div style="text-align:center;margin-bottom:22px">
            <div style="display:inline-block;background:linear-gradient(120deg,#ffd9a6,#ffe9cf);color:#e85d00;font-weight:800;font-size:12.5px;letter-spacing:1px;padding:7px 16px;border-radius:20px">SELLER ONBOARDING</div>
            <h1 style="font-size:26px;font-weight:900;margin:14px 0 6px">망고샵 수입사 입점 신청</h1>
            <p class="muted" style="font-size:14px">초대받은 <b style="color:var(--navy-800)">{{ $invite->email }}</b> 계정으로 입점 스토어를 개설합니다.</p>
        </div>

        <div class="form-card" style="padding:28px 30px">
            <form method="POST" action="{{ route('seller.invite.accept', $invite->token) }}">
                @csrf
                <div class="field"><label>수입사명 <span class="req">*</span></label>
                    <input type="text" name="company_name" class="input" value="{{ old('company_name', $invite->company_name) }}" required placeholder="예: 트로피컬수입">
                </div>
                <div class="row2">
                    <div class="field"><label>담당자 이름 <span class="req">*</span></label><input type="text" name="name" class="input" value="{{ old('name') }}" required></div>
                    <div class="field"><label>연락처</label><input type="text" name="phone" class="input" value="{{ old('phone') }}" placeholder="02-000-0000"></div>
                </div>
                <div class="row2">
                    <div class="field"><label>주력 원산지</label><input type="text" name="origin_focus" class="input" value="{{ old('origin_focus', $invite->origin_focus) }}" placeholder="예: 태국"></div>
                    <div class="field"><label>사업자번호</label><input type="text" name="biz_no" class="input" value="{{ old('biz_no') }}" placeholder="000-00-00000"></div>
                </div>
                <div class="row2">
                    <div class="field"><label>비밀번호 <span class="req">*</span></label><input type="password" name="password" class="input" required minlength="8" placeholder="8자 이상"></div>
                    <div class="field"><label>비밀번호 확인 <span class="req">*</span></label><input type="password" name="password_confirmation" class="input" required minlength="8"></div>
                </div>
                <p class="muted" style="font-size:12.5px;margin:4px 0 16px">신청 완료 시 판매자 계정이 생성되고 전용 스토어가 개설됩니다. 로그인 이메일은 <b>{{ $invite->email }}</b> 입니다.</p>
                <button type="submit" class="btn btn-primary btn-lg btn-block">입점 신청 완료</button>
            </form>
        </div>
    @endif
</div>
@endsection
