@extends('layouts.admin')
@section('title', '레시피 관리')
@section('heading', '레시피 관리')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="제목 검색" class="ainput" style="width:200px">
        <select name="category" class="ainput" style="width:170px">
            <option value="">전체 카테고리</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="kind" class="ainput" style="width:130px">
            <option value="">전체</option>
            <option value="official" @selected(request('kind')==='official')>공식</option>
            <option value="user" @selected(request('kind')==='user')>사용자 글</option>
        </select>
        <button class="abtn abtn-ghost abtn-sm">검색</button>
    </form>
    <a href="{{ route('admin.recipes.create') }}" class="abtn abtn-primary">+ 레시피 등록</a>
</div>

<div class="adm-card" style="padding:0;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:13.5px">
        <thead><tr style="background:#f6f8fc;text-align:left">
            <th style="padding:11px 14px">제목</th><th style="padding:11px">카테고리</th><th style="padding:11px">구분</th>
            <th style="padding:11px">고정</th><th style="padding:11px">상태</th><th style="padding:11px">조회</th><th style="padding:11px 14px">관리</th>
        </tr></thead>
        <tbody>
        @forelse($recipes as $r)
            <tr style="border-top:1px solid #eef1f6">
                <td style="padding:10px 14px"><a href="{{ route('admin.recipes.edit',$r) }}" style="font-weight:600;color:#1f2b3d;text-decoration:none">{{ $r->title }}</a></td>
                <td style="padding:10px">{{ $r->category->name ?? '-' }}</td>
                <td style="padding:10px">{{ $r->user_id ? '사용자('.($r->user->name ?? '?').')' : '공식' }}</td>
                <td style="padding:10px">
                    <form method="POST" action="{{ route('admin.recipes.togglepin',$r) }}">@csrf
                        <button class="abtn abtn-sm {{ $r->is_pinned ? 'abtn-primary' : 'abtn-ghost' }}">{{ $r->is_pinned ? '고정' : '일반' }}</button>
                    </form>
                </td>
                <td style="padding:10px">
                    <form method="POST" action="{{ route('admin.recipes.togglestatus',$r) }}">@csrf
                        <button class="abtn abtn-sm abtn-ghost" style="color:{{ $r->status==='published'?'#159a4f':'#e0322d' }}">{{ $r->status==='published'?'공개':'숨김' }}</button>
                    </form>
                </td>
                <td style="padding:10px">{{ number_format($r->view_count) }}</td>
                <td style="padding:10px 14px;white-space:nowrap">
                    <a href="{{ route('admin.recipes.edit',$r) }}" class="abtn abtn-ghost abtn-sm">수정</a>
                    <form method="POST" action="{{ route('admin.recipes.destroy',$r) }}" style="display:inline" onsubmit="return confirm('삭제할까요?')">@csrf @method('DELETE')
                        <button class="abtn abtn-ghost abtn-sm" style="color:#e0322d">삭제</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" style="padding:30px;text-align:center;color:#8a93a8">등록된 레시피가 없습니다. 먼저 <a href="{{ route('admin.index','recipe_categories') }}">레시피 카테고리</a>를 추가한 뒤 등록하세요.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:16px">{{ $recipes->links() }}</div>
@endsection
