@extends('layouts.admin')
@section('title', '방문이력')
@section('heading', '방문이력')

@section('content')
@php($type = request('type'))
@php($platform = request('platform'))
@php($typeLabels = \App\Models\ActivityLog::TYPES)

{{-- 요약 타일 (오늘) --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px">
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">오늘 방문</div><div style="font-size:22px;font-weight:800;color:var(--a-navy)">{{ number_format($stats['today_visit']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">오늘 상품검색</div><div style="font-size:22px;font-weight:800">{{ number_format($stats['today_search']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">오늘 상품조회</div><div style="font-size:22px;font-weight:800">{{ number_format($stats['today_view']) }}</div></div>
    <div class="adm-card" style="padding:16px 18px"><div style="font-size:12.5px;color:#8a93a8">오늘 전체 활동</div><div style="font-size:22px;font-weight:800">{{ number_format($stats['today_total']) }}</div></div>
</div>

{{-- 인기 검색어 / 인기 조회 상품 (최근 7일) --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px">
    <div class="adm-card" style="padding:16px 18px">
        <div style="font-size:13px;font-weight:700;margin-bottom:10px">🔍 인기 검색어 <span style="color:#97a0b8;font-weight:400;font-size:11.5px">최근 7일</span></div>
        @forelse($topKeywords as $k)
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;border-top:1px solid #f3f5f9"><span>{{ $loop->iteration }}. {{ $k->keyword }}</span><span style="color:#8a93a8">{{ number_format($k->cnt) }}회</span></div>
        @empty
            <div style="color:#97a0b8;font-size:13px;padding:6px 0">검색 이력이 없습니다.</div>
        @endforelse
    </div>
    <div class="adm-card" style="padding:16px 18px">
        <div style="font-size:13px;font-weight:700;margin-bottom:10px">👀 인기 조회 상품 <span style="color:#97a0b8;font-weight:400;font-size:11.5px">최근 7일</span></div>
        @forelse($topProducts as $p)
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;border-top:1px solid #f3f5f9"><span>{{ $loop->iteration }}. {{ \Illuminate\Support\Str::limit($p->product_name, 28) }}</span><span style="color:#8a93a8">{{ number_format($p->cnt) }}회</span></div>
        @empty
            <div style="color:#97a0b8;font-size:13px;padding:6px 0">조회 이력이 없습니다.</div>
        @endforelse
    </div>
</div>

<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.visit-history.index', array_filter(['platform'=>$platform])) }}" class="{{ !$type ? 'on' : '' }}">전체</a>
        @foreach($typeLabels as $k => $label)
            <a href="{{ route('admin.visit-history.index', array_filter(['type'=>$k,'platform'=>$platform])) }}" class="{{ $type===$k ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="spacer"></div>
    <div class="filter-tabs">
        <a href="{{ route('admin.visit-history.index', array_filter(['type'=>$type])) }}" class="{{ !$platform ? 'on' : '' }}">전체</a>
        <a href="{{ route('admin.visit-history.index', array_filter(['type'=>$type,'platform'=>'web'])) }}" class="{{ $platform==='web' ? 'on' : '' }}">웹</a>
        <a href="{{ route('admin.visit-history.index', array_filter(['type'=>$type,'platform'=>'app'])) }}" class="{{ $platform==='app' ? 'on' : '' }}">앱</a>
    </div>
    <form method="GET" class="search-mini">
        @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
        @if($platform)<input type="hidden" name="platform" value="{{ $platform }}">@endif
        <input type="text" name="q" value="{{ request('q') }}" placeholder="회원/검색어/상품/경로/IP">
        <button><x-icon name="search" :size="16"/></button>
    </form>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr>
            <th style="width:140px">시각</th><th style="width:84px">유형</th><th style="width:54px">경로</th>
            <th style="width:180px">사용자</th><th>내용</th><th style="width:120px">IP</th>
        </tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td style="white-space:nowrap">{{ $log->created_at->format('m.d H:i:s') }}</td>
                <td>
                    @php($pill = ['visit'=>'pill-w','search'=>'pill-y','product_view'=>'pill-y','login'=>($log->status==='fail'?'pill-n':'pill-w')])
                    <span class="pill {{ $pill[$log->type] ?? 'pill-w' }}">{{ $log->typeLabel() }}</span>
                </td>
                <td><span class="pill {{ $log->platform==='app' ? 'pill-y':'pill-w' }}">{{ $log->platformLabel() }}</span></td>
                <td>
                    @if($log->user)
                        <b>{{ $log->user->name }}</b><div style="font-size:11.5px;color:#97a0b8">{{ $log->user->email }}</div>
                    @else
                        <span style="color:#97a0b8">게스트</span>
                        @if($log->email)<div style="font-size:11.5px;color:#97a0b8">{{ $log->email }}</div>@endif
                    @endif
                </td>
                <td style="font-size:12.5px">
                    @switch($log->type)
                        @case('search')
                            <b>“{{ $log->keyword }}”</b> @if(!is_null($log->result_count))<span style="color:#97a0b8">· 결과 {{ number_format($log->result_count) }}건</span>@endif
                            @break
                        @case('product_view')
                            @if($log->product)<a href="{{ route('catalog.show', $log->product->slug) }}" target="_blank" style="color:var(--a-navy)">{{ $log->product_name }}</a>@else{{ $log->product_name }}@endif
                            @break
                        @case('login')
                            @if($log->status==='fail')<span style="color:#e0322d;font-weight:700">로그인 실패</span>@else<span style="color:#16a34a;font-weight:700">로그인 성공</span>@endif
                            @break
                        @default
                            <span style="color:#6b7794">{{ $log->path }}</span>
                    @endswitch
                </td>
                <td style="font-size:12px;color:#8a93a8">{{ $log->ip }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#97a0b8;padding:40px">방문이력이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $logs->links('pagination.simple') }}
@endsection
