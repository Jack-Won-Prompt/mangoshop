@extends('layouts.app')
@section('title', '회원정보수정 — 망고샵')

@section('content')
<div class="page-head"><div class="container"><h1>회원정보수정</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            {{-- 반려 시 사업자등록증 재업로드가 같은 폼에서 이뤄지므로 enctype 지정 (폼 중첩은 HTML상 불가) --}}
            <form method="POST" action="{{ route('mypage.profile.update') }}" class="form-card" enctype="multipart/form-data">
                @csrf @method('PUT')
                <h3><x-icon name="user"/> 기본정보</h3>
                <div class="row2">
                    <div class="field"><label>이름 <span class="req">*</span></label><input type="text" name="name" class="input" value="{{ old('name', $user->name) }}" required></div>
                    <div class="field"><label>이메일</label><input type="email" class="input" value="{{ $user->email }}" disabled></div>
                </div>
                <div class="field"><label>연락처</label><input type="text" name="phone" class="input" value="{{ old('phone', $user->phone) }}"></div>

                @if($user->member_type==='business')
                    <div style="background:var(--navy-50);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13.5px">
                        <b style="color:var(--navy-800)">사업자정보</b> · {{ $user->company_name }} ({{ $user->biz_no }})
                        {{-- 심사 상태를 대기/승인/반려 세 가지로 구분해 표시 --}}
                        @if($user->biz_status === 'approved')
                            <span class="badge badge-new">승인완료</span>
                        @elseif($user->biz_status === 'rejected')
                            <span class="badge badge-hot">반려됨</span>
                        @else
                            <span class="badge badge-hot">승인대기</span>
                        @endif

                        @if($user->biz_submitted_at)
                            <div style="margin-top:6px;color:var(--slate-500);font-size:12.5px">제출일시 : {{ $user->biz_submitted_at }}</div>
                        @endif

                        @if($user->biz_status === 'rejected')
                            {{-- 반려 사유를 알려주고 바로 다시 올릴 수 있도록 재업로드 칸 제공 --}}
                            <div style="margin-top:10px;color:#b42318">
                                반려 사유 : {{ $user->biz_reject_reason ?: '관리자에게 문의해 주세요.' }}
                            </div>
                            <div class="field" style="margin-top:12px;margin-bottom:0">
                                <label>사업자등록증 재업로드</label>
                                <input type="file" name="biz_license" class="input" accept=".pdf,image/*">
                                <div style="margin-top:6px;color:var(--slate-500);font-size:12.5px">PDF 또는 이미지 파일만 가능하며, 용량은 10MB 이하여야 합니다.</div>
                            </div>
                        @elseif($user->biz_status !== 'approved')
                            <div style="margin-top:8px;color:var(--slate-500);font-size:12.5px">
                                제출하신 사업자등록증을 관리자가 확인 중입니다. 승인 전까지는 도매가가 표시되지 않습니다.
                            </div>
                        @endif
                    </div>
                @endif

                <h3 style="margin-top:24px"><x-icon name="pin"/> 기본 배송지</h3>
                <div class="field" style="max-width:200px"><label>우편번호</label><input type="text" name="postcode" class="input" value="{{ old('postcode', $user->postcode) }}"></div>
                <div class="field"><label>주소</label><input type="text" name="address1" class="input" value="{{ old('address1', $user->address1) }}"></div>
                <div class="field"><label>상세주소</label><input type="text" name="address2" class="input" value="{{ old('address2', $user->address2) }}"></div>

                <h3 style="margin-top:24px"><x-icon name="shield"/> 비밀번호 변경 (선택)</h3>
                <div class="row2">
                    <div class="field"><label>새 비밀번호</label><input type="password" name="password" class="input" placeholder="변경 시에만 입력"></div>
                    <div class="field"><label>비밀번호 확인</label><input type="password" name="password_confirmation" class="input"></div>
                </div>

                <button class="btn btn-primary btn-lg">저장하기</button>
            </form>

            <div style="text-align:right;margin-top:20px;padding-top:16px;border-top:1px solid var(--line)">
                <a href="{{ route('mypage.withdraw') }}" style="font-size:13px;color:var(--slate-500);text-decoration:underline">회원 탈퇴</a>
            </div>
        </div>
    </div>
</div>
@endsection
