@extends('layouts.admin')
@section('title', '오류 로그')
@section('heading', '운영 오류 로그')

@section('content')
<div class="toolbar">
    <div class="search-mini" style="gap:6px">
        @php($s = request('source'))@php($st = request('status'))
        <a href="{{ route('admin.errors.index', ['status'=>$st]) }}" class="abtn {{ !$s ? 'abtn-pri' : 'abtn-ghost' }} abtn-sm">전체</a>
        <a href="{{ route('admin.errors.index', ['source'=>'server','status'=>$st]) }}" class="abtn {{ $s==='server' ? 'abtn-pri' : 'abtn-ghost' }} abtn-sm">서버</a>
        <a href="{{ route('admin.errors.index', ['source'=>'client','status'=>$st]) }}" class="abtn {{ $s==='client' ? 'abtn-pri' : 'abtn-ghost' }} abtn-sm">화면</a>
    </div>
    <div class="spacer"></div>
    <a href="{{ route('admin.errors.index', ['source'=>$s]) }}" class="abtn {{ $st!=='resolved' && $st!=='all' ? 'abtn-pri' : 'abtn-ghost' }} abtn-sm">미해결 {{ $openCount }}</a>
    <a href="{{ route('admin.errors.index', ['source'=>$s,'status'=>'resolved']) }}" class="abtn {{ $st==='resolved' ? 'abtn-pri' : 'abtn-ghost' }} abtn-sm">해결됨</a>
    <a href="{{ route('admin.errors.index', ['source'=>$s,'status'=>'all']) }}" class="abtn {{ $st==='all' ? 'abtn-pri' : 'abtn-ghost' }} abtn-sm">모두</a>
</div>

<div class="adm-card">
    <table class="atable">
        <thead>
            <tr>
                <th style="width:64px">구분</th>
                <th>오류</th>
                <th style="width:60px">횟수</th>
                <th style="width:140px">최근 발생</th>
                <th style="width:70px">SR</th>
                <th style="width:180px">관리</th>
            </tr>
        </thead>
        <tbody>
        @forelse($events as $e)
            <tr style="{{ $e->resolved_at ? 'opacity:.55' : '' }}">
                <td><span class="abadge {{ $e->source==='server' ? 'red' : 'blue' }}">{{ $e->source==='server' ? '서버' : '화면' }}</span></td>
                <td>
                    <a href="{{ route('admin.errors.show', $e) }}" style="font-weight:600;color:#1f2937;text-decoration:none">{{ \Illuminate\Support\Str::limit($e->message, 80) ?: '(메시지 없음)' }}</a>
                    <div style="font-size:11.5px;color:#97a0b8;margin-top:2px">{{ $e->exception }} @if($e->file)· {{ \Illuminate\Support\Str::limit(basename($e->file), 40) }}@if($e->line):{{ $e->line }}@endif @endif</div>
                </td>
                <td><b>{{ $e->count }}</b></td>
                <td style="font-size:12.5px;color:#54607a">{{ optional($e->last_seen_at)->format('Y-m-d H:i') }}</td>
                <td>@if($e->sr_thread_id)<a href="{{ route('admin.sr.show', $e->sr_thread_id) }}" class="abtn abtn-ghost abtn-sm">열기</a>@else - @endif</td>
                <td>
                    <a href="{{ route('admin.errors.show', $e) }}" class="abtn abtn-ghost abtn-sm">상세</a>
                    <form method="POST" action="{{ route('admin.errors.resolve', $e) }}" style="display:inline">@csrf
                        <button class="abtn {{ $e->resolved_at ? 'abtn-ghost' : 'abtn-pri' }} abtn-sm">{{ $e->resolved_at ? '되돌리기' : '해결' }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#97a0b8;padding:40px">수집된 오류가 없습니다. 🎉</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:12px">{{ $events->links('pagination.simple') }}</div>

<style>
    .abadge{display:inline-block;font-size:11px;font-weight:700;border-radius:999px;padding:3px 9px}
    .abadge.red{background:#fef2f2;color:#b4232a}
    .abadge.blue{background:#eef2ff;color:#3730a3}
</style>
@endsection
