@extends('layouts.admin')
@section('title', '레시피 Q&A')
@section('heading', '레시피 Q&A 관리')

@section('content')
<div class="adm-card" style="padding:0;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:13.5px">
        <thead><tr style="background:#f6f8fc;text-align:left">
            <th style="padding:11px 14px">질문</th><th style="padding:11px">카테고리</th><th style="padding:11px">작성자</th>
            <th style="padding:11px">답변</th><th style="padding:11px">상태</th><th style="padding:11px 14px">관리</th>
        </tr></thead>
        <tbody>
        @forelse($questions as $q)
            <tr style="border-top:1px solid #eef1f6">
                <td style="padding:10px 14px"><a href="{{ route('community.recipe.qna.show',$q) }}" target="_blank" style="color:#1f2b3d;text-decoration:none;font-weight:600">{{ \Illuminate\Support\Str::limit($q->title,45) }}</a></td>
                <td style="padding:10px">{{ $q->category->name ?? '일반' }}</td>
                <td style="padding:10px">{{ $q->user->name ?? '회원' }}</td>
                <td style="padding:10px">{{ $q->answers_count }}</td>
                <td style="padding:10px">
                    <form method="POST" action="{{ route('admin.recipe-qna.toggle',$q) }}">@csrf
                        <button class="abtn abtn-sm abtn-ghost" style="color:{{ $q->status==='published'?'#159a4f':'#e0322d' }}">{{ $q->status==='published'?'공개':'숨김' }}</button>
                    </form>
                </td>
                <td style="padding:10px 14px;white-space:nowrap">
                    <form method="POST" action="{{ route('admin.recipe-qna.destroy',$q) }}" style="display:inline" onsubmit="return confirm('질문과 답변을 모두 삭제할까요?')">@csrf @method('DELETE')
                        <button class="abtn abtn-ghost abtn-sm" style="color:#e0322d">삭제</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:30px;text-align:center;color:#8a93a8">등록된 질문이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:16px">{{ $questions->links() }}</div>
<p style="color:#8a93a8;font-size:12.5px;margin-top:12px">※ 답변 등록/삭제는 각 질문 상세 페이지에서 가능합니다(관리자 답변은 자동으로 "관리자" 표시).</p>
@endsection
