@php($site = config('site'))
@php($typeLabel = \App\Models\Inquiry::TYPES[$inquiry->type] ?? $inquiry->type)
<div style="font-family:'Malgun Gothic','Apple SD Gothic Neo',sans-serif;font-size:14px;color:#2b2113;line-height:1.7;max-width:600px;margin:0 auto">
    <div style="background:linear-gradient(135deg,#ff8a1e,#ffb347);border-radius:14px 14px 0 0;padding:22px 24px;color:#fff">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.5px">🥭 {{ $site['name'] ?? '망고샵' }}</div>
        <div style="opacity:.92;margin-top:4px">새 고객문의가 접수되었습니다</div>
    </div>
    <div style="border:1px solid #f0e6d6;border-top:0;border-radius:0 0 14px 14px;padding:24px">
        <table style="width:100%;border-collapse:collapse;font-size:13.5px;margin:0 0 16px">
            <tr><td style="padding:7px 0;color:#8a7a5f;width:90px">유형</td><td style="padding:7px 0"><b>{{ $typeLabel }}</b></td></tr>
            <tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">제목</td><td style="padding:7px 0;border-top:1px solid #f2eadd"><b>{{ $inquiry->subject }}</b></td></tr>
            <tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">작성자</td><td style="padding:7px 0;border-top:1px solid #f2eadd">{{ $inquiry->name }}@if($inquiry->phone) · {{ $inquiry->phone }}@endif @if($inquiry->email) · {{ $inquiry->email }}@endif</td></tr>
            <tr><td style="padding:7px 0;color:#8a7a5f;border-top:1px solid #f2eadd">접수일</td><td style="padding:7px 0;border-top:1px solid #f2eadd">{{ $inquiry->created_at->format('Y.m.d H:i') }}@if($inquiry->is_secret) · 🔒 비밀글 @endif</td></tr>
        </table>

        <div style="background:#fff8ef;border:1px solid #f2eadd;border-radius:10px;padding:14px 16px;font-size:14px;line-height:1.8;white-space:pre-wrap;color:#3d2a12">{{ $inquiry->body }}</div>

        <div style="text-align:center;margin:22px 0 6px">
            <a href="{{ route('admin.inquiries.show', $inquiry) }}" style="display:inline-block;background:#ff6b00;color:#fff;font-weight:800;font-size:15px;text-decoration:none;padding:13px 32px;border-radius:30px">관리자에서 답변하기 →</a>
        </div>

        <hr style="border:0;border-top:1px solid #f0e6d6;margin:20px 0">
        <div style="font-size:12px;color:#a99a80;line-height:1.8">
            <b>{{ $site['company'] ?? '망고샵' }}</b> · 고객센터 {{ $site['cs_tel'] ?? '' }}<br>
            이 메일은 고객 문의 접수 시 자동 발송됩니다. 답장 시 고객({{ $inquiry->email ?: '이메일 미기재' }})에게 직접 전달됩니다.
        </div>
    </div>
</div>
