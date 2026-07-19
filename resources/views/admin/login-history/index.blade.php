@extends('layouts.admin')
@section('title', '로그인 이력')
@section('heading', '로그인 이력')

@section('content')
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px">
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a92a6">전체 시도</div><div style="font-size:24px;font-weight:900">{{ number_format($stats['total']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a92a6">성공</div><div style="font-size:24px;font-weight:900;color:#16a34a">{{ number_format($stats['success']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a92a6">실패</div><div style="font-size:24px;font-weight:900;color:#e0322d">{{ number_format($stats['fail']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a92a6">오늘</div><div style="font-size:24px;font-weight:900">{{ number_format($stats['today']) }}</div></div>
</div>

<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.login-history.index', array_filter(['email'=>request('email')])) }}" class="{{ !request('status') ? 'on' : '' }}">전체</a>
        <a href="{{ route('admin.login-history.index', array_filter(['status'=>'success','email'=>request('email')])) }}" class="{{ request('status')==='success' ? 'on' : '' }}">성공</a>
        <a href="{{ route('admin.login-history.index', array_filter(['status'=>'fail','email'=>request('email')])) }}" class="{{ request('status')==='fail' ? 'on' : '' }}">실패</a>
    </div>
    <div class="spacer"></div>
    <form method="GET" class="search-mini">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <input type="text" name="email" value="{{ request('email') }}" placeholder="이메일 검색">
        <button><x-icon name="search" :size="16"/></button>
    </form>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th style="width:150px">일시</th><th>이메일</th><th>회원</th><th style="width:80px">결과</th><th style="width:130px">IP</th><th>접속 환경</th></tr></thead>
        <tbody>
        @forelse($histories as $h)
            <tr>
                <td>{{ $h->created_at->format('Y.m.d H:i:s') }}</td>
                <td>{{ $h->email ?: '-' }}</td>
                <td>
                    @if($h->user)
                        <a href="{{ route('admin.users.show', $h->user) }}"><b>{{ $h->user->name }}</b></a>
                        @if($h->user->is_admin)<span class="pill pill-b">관리자</span>@endif
                    @else
                        <span style="color:#cbd2e0">—</span>
                    @endif
                </td>
                <td>
                    @if($h->status==='success')<span class="pill pill-y">성공</span>
                    @else<span class="pill pill-n">실패</span>@endif
                </td>
                <td style="font-size:12.5px;color:#5a6478">{{ $h->ip ?: '-' }}</td>
                <td style="font-size:11.5px;color:#8a92a6;max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $h->user_agent ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#97a0b8;padding:34px">로그인 이력이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $histories->links('pagination.simple') }}
@endsection
