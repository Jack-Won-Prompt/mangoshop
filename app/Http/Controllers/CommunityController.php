<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Inquiry;
use App\Models\Notice;
use App\Models\Review;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function notices()
    {
        $notices = Notice::orderByDesc('is_pinned')->latest('published_at')->paginate(15);

        return view('community.notices', compact('notices'));
    }

    public function notice(Notice $notice)
    {
        $notice->increment('views');

        return view('community.notice', compact('notice'));
    }

    public function reviews()
    {
        $reviews = Review::visible()->with('product')->latest()->paginate(15);

        return view('community.reviews', compact('reviews'));
    }

    public function faq(Request $request)
    {
        $faqs = Faq::orderBy('sort_order')->get()->groupBy('category');

        return view('community.faq', compact('faqs'));
    }

    public function qna()
    {
        $inquiries = Inquiry::latest()->paginate(15);

        return view('community.qna', compact('inquiries'));
    }

    public function inquiryForm(Request $request)
    {
        $type = $request->get('type', 'qna');

        return view('community.inquiry', ['type' => $type]);
    }

    public function inquiryStore(Request $request)
    {
        // 1) 허니팟 — 사람에겐 안 보이는 필드. 봇이 채우면 조용히 접수처럼 종료(미저장·미알림).
        if (filled($request->input('website'))) {
            return $this->inquiryDone($request);
        }

        // 2) 레이트리밋 — IP 기준 1시간 5건 초과 시 차단(실사용자 피드백 제공).
        $rlKey = 'inquiry:'.$request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rlKey, 5)) {
            $msg = '문의가 너무 자주 접수되었습니다. 잠시 후 다시 시도해 주세요.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 429)
                : back()->withInput()->with('error', $msg);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rlKey, 3600);

        $data = $request->validate([
            'type'    => ['required', 'in:quote,qna,request'],
            'name'    => ['required', 'string', 'max:50'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'email'   => ['nullable', 'email', 'max:100'],
            'subject' => ['required', 'string', 'max:150'],
            'body'    => ['required', 'string', 'max:3000'],
            'is_secret' => ['nullable', 'boolean'],
        ]);

        // 3) 콘텐츠 스팸 필터 — 스캐너/광고봇 패턴이면 조용히 종료(미저장·미알림).
        if ($this->looksLikeSpam($data)) {
            return $this->inquiryDone($request);
        }

        $inquiry = Inquiry::create($data + [
            'user_id'   => $request->user()?->id,
            'is_secret' => $request->boolean('is_secret'),
            'status'    => 'pending',
        ]);

        // 관리자 알림 이메일 발송(설정된 수신자에게). 실패해도 접수는 유지.
        $emails = array_values(array_filter((array) config('site.inquiry_emails', [])));
        if ($emails) {
            try {
                \Illuminate\Support\Facades\Mail::to($emails)->send(new \App\Mail\InquiryNotifyMail($inquiry));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // 관리자 앱 FCM 푸시 알림(미설정/토큰없음 시 자동 무시)
        try {
            $adminIds = \App\Models\User::where('is_admin', true)->pluck('id');
            if ($adminIds->isNotEmpty()) {
                app(\App\Services\FcmService::class)->sendToUsers(
                    $adminIds,
                    '새 고객문의',
                    (\App\Models\Inquiry::TYPES[$inquiry->type] ?? $inquiry->type).' · '.$inquiry->subject,
                    ['type' => 'inquiry', 'inquiry_id' => $inquiry->id],
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('community.qna')->with('ok', '문의가 접수되었습니다. 빠르게 답변드리겠습니다.');
    }

    /** 스팸 판정 시 사람에겐 정상 접수처럼 보이는 응답(실제 저장·알림 없음). */
    private function inquiryDone(Request $request)
    {
        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => '문의가 접수되었습니다.'])
            : redirect()->route('community.qna')->with('ok', '문의가 접수되었습니다. 빠르게 답변드리겠습니다.');
    }

    /** 스캐너/광고봇 문의 패턴 판정(보수적). */
    private function looksLikeSpam(array $data): bool
    {
        $email   = strtolower(trim($data['email'] ?? ''));
        $subject = trim($data['subject'] ?? '');
        $body    = trim($data['body'] ?? '');

        // 이메일 블록리스트(스캐너 기본값·SEO 광고 도메인)
        if ($email !== '' && (str_starts_with($email, 'testing@example.com') || str_contains($email, 'search-mangoshop.co.kr'))) {
            return true;
        }
        // 지나치게 짧은 제목/본문(예: "1")
        if (mb_strlen($subject) < 2 || mb_strlen($body) < 4) {
            return true;
        }
        // 본문/제목 내 URL 2개 이상(링크 스팸)
        if (preg_match_all('#https?://#i', $subject.' '.$body) >= 2) {
            return true;
        }

        return false;
    }
}
