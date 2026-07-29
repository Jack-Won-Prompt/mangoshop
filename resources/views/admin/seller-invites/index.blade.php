@extends('layouts.admin')
@section('title', '입점 초대')
@section('heading', '입점 초대')

@section('content')
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px">
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a92a6">전체 초대</div><div style="font-size:24px;font-weight:900">{{ number_format($stats['total']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a92a6">대기중</div><div style="font-size:24px;font-weight:900;color:#e0322d">{{ number_format($stats['pending']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a92a6">수락완료</div><div style="font-size:24px;font-weight:900;color:#16a34a">{{ number_format($stats['accepted']) }}</div></div>
</div>

{{-- 초대 발송 --}}
<div class="adm-card" style="margin-bottom:20px">
    <div class="h">수입사 초대 메일 발송</div>
    <form method="POST" action="{{ route('admin.seller-invites.store') }}" style="padding:18px;display:grid;grid-template-columns:1.4fr 1.2fr 1fr auto;gap:10px;align-items:end">
        @csrf
        <div class="afield" style="margin:0"><label>이메일 <span style="color:#e0322d">*</span></label><input type="email" name="email" class="ainput" placeholder="seller@example.com" required></div>
        <div class="afield" style="margin:0"><label>수입사명(제안)</label><input type="text" name="company_name" class="ainput" placeholder="예: 트로피컬수입"></div>
        <div class="afield" style="margin:0"><label>주력 원산지</label><input type="text" name="origin_focus" class="ainput" placeholder="예: 태국"></div>
        <button class="abtn abtn-pri" style="white-space:nowrap">초대 발송</button>
    </form>
    <p style="padding:0 18px 16px;margin:0;font-size:12.5px;color:#8a92a6">초대 링크는 14일간 유효합니다. 수락 시 판매자 계정과 전용 스토어가 생성됩니다.</p>
</div>

{{-- 초대 목록 --}}
<div class="adm-card">
    <table class="atable">
        <thead><tr><th>이메일</th><th>수입사명</th><th>원산지</th><th>상태</th><th>발송일</th><th style="text-align:right">관리</th></tr></thead>
        <tbody>
        @forelse($invites as $iv)
            <tr>
                <td><b>{{ $iv->email }}</b></td>
                <td>{{ $iv->company_name ?: '-' }}</td>
                <td>{{ $iv->origin_focus ?: '-' }}</td>
                <td>
                    @php($lbl = $iv->statusLabel())
                    <span class="pill {{ $lbl==='수락완료'?'pill-y':($lbl==='대기중'?'pill-w':'pill-n') }}">{{ $lbl }}</span>
                </td>
                <td>{{ $iv->created_at->format('Y.m.d') }}</td>
                <td style="text-align:right;white-space:nowrap">
                    @if($iv->status !== 'accepted')
                        <form method="POST" action="{{ route('admin.seller-invites.resend', $iv) }}" style="display:inline">@csrf
                            <button class="abtn abtn-ghost abtn-sm" type="submit">재발송</button>
                        </form>
                        <form method="POST" action="{{ route('admin.seller-invites.revoke', $iv) }}" style="display:inline" onsubmit="return confirm('초대를 취소하시겠습니까?')">@csrf @method('DELETE')
                            <button class="abtn abtn-ghost abtn-sm" type="submit">취소</button>
                        </form>
                    @else
                        <span style="color:#97a0b8;font-size:12.5px">{{ optional($iv->accepted_at)->format('Y.m.d') }} 수락</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#97a0b8;padding:34px">발송한 초대가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $invites->links('pagination.simple') }}
@endsection
