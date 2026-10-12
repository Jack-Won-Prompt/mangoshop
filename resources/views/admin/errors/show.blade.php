@extends('layouts.admin')
@section('title', '오류 상세')
@section('heading', '오류 상세')

@section('content')
<div style="margin-bottom:12px">
    <a href="{{ route('admin.errors.index') }}" class="abtn abtn-ghost abtn-sm">← 목록</a>
    @if($event->sr_thread_id)<a href="{{ route('admin.sr.show', $event->sr_thread_id) }}" class="abtn abtn-ghost abtn-sm">관련 SR 열기</a>@endif
    <form method="POST" action="{{ route('admin.errors.resolve', $event) }}" style="display:inline">@csrf
        <button class="abtn {{ $event->resolved_at ? 'abtn-ghost' : 'abtn-pri' }} abtn-sm">{{ $event->resolved_at ? '미해결로' : '해결 처리' }}</button>
    </form>
    <form method="POST" action="{{ route('admin.errors.destroy', $event) }}" style="display:inline" onsubmit="return confirm('삭제할까요?')">@csrf @method('DELETE')
        <button class="abtn abtn-red abtn-sm">삭제</button>
    </form>
</div>

<div class="adm-card" style="padding:20px;display:grid;gap:10px;max-width:980px">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <span class="abadge {{ $event->source==='server' ? 'red' : 'blue' }}">{{ $event->source==='server' ? '서버' : '화면 스크립트' }}</span>
        <b>{{ $event->exception }}</b>
        <span style="color:#97a0b8;font-size:12.5px">· {{ $event->count }}회 · 최근 {{ optional($event->last_seen_at)->format('Y-m-d H:i') }}</span>
        @if($event->resolved_at)<span class="abadge" style="background:#ecfdf3;color:#15803d">해결됨</span>@endif
    </div>
    <div style="font-size:15px;font-weight:600;line-height:1.6">{{ $event->message }}</div>
    <dl class="kv">
        @if($event->file)<div><dt>위치</dt><dd>{{ $event->file }}@if($event->line):{{ $event->line }}@endif</dd></div>@endif
        @if($event->url)<div><dt>주소</dt><dd>{{ $event->method }} {{ $event->url }}</dd></div>@endif
        @if($event->user_agent)<div><dt>User-Agent</dt><dd>{{ $event->user_agent }}</dd></div>@endif
        <div><dt>처음 발생</dt><dd>{{ $event->created_at->format('Y-m-d H:i') }}</dd></div>
    </dl>
    @if($event->trace)
        <div>
            <div style="font-weight:700;margin:6px 0">스택</div>
            <pre style="background:#0d1117;color:#e6edf3;border-radius:9px;padding:14px;overflow:auto;font-size:12px;line-height:1.6;max-height:420px">{{ $event->trace }}</pre>
        </div>
    @endif
</div>

<style>
    .kv{display:grid;gap:6px;margin:4px 0}
    .kv>div{display:grid;grid-template-columns:120px 1fr;gap:10px;font-size:13px}
    .kv dt{color:#97a0b8;margin:0}
    .kv dd{margin:0;word-break:break-all}
    .abadge{display:inline-block;font-size:11px;font-weight:700;border-radius:999px;padding:3px 9px}
    .abadge.red{background:#fef2f2;color:#b4232a}
    .abadge.blue{background:#eef2ff;color:#3730a3}
</style>
@endsection
