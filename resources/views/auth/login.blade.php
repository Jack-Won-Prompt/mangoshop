@extends('layouts.app')
@section('title', '로그인 — 망고샵')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <a href="{{ route('home') }}" class="brand" style="justify-content:center"><img src="{{ asset('images/logo.svg') }}" alt="망고샵" class="brand-logo" style="height:46px"></a>
        <h2>로그인</h2>
        <p class="sub">수입 과일 전문 쇼핑몰 망고샵</p>

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" class="input" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label>비밀번호</label>
                <input type="password" name="password" class="input" required>
            </div>
            <label class="inline" style="font-size:13px;margin-bottom:16px"><input type="checkbox" name="remember"> 로그인 상태 유지</label>
            <button class="btn btn-primary btn-lg btn-block">로그인</button>
        </form>

        <div class="auth-links">
            <a href="{{ route('register') }}">회원가입</a>
            <span>·</span>
            <a href="{{ route('community.qna') }}">고객센터 문의</a>
        </div>

        <div style="margin-top:20px;padding:14px;background:var(--slate-50);border-radius:10px;font-size:12.5px;color:var(--slate-500);line-height:1.8">
            <b>체험 계정</b><br>
            소매회원 user@test.com / test1234<br>
            도매(승인) buyer@test.com / test1234<br>
            관리자 admin@mangoshop.co.kr / mango!2026
        </div>
    </div>
</div>
@endsection
