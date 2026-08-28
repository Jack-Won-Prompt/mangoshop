<?php

namespace App\Services;

use App\Services\Popbill\PopbillMessageService;
use Illuminate\Support\Facades\Log;

/**
 * 주문 알림 문자 발송(팝빌). 실패해도 예외 없이 false 를 반환해 주문 흐름을 막지 않는다.
 *  - config('popbill.sms.simulate') = true 면 실제 발송 없이 로그만 남긴다(기본).
 */
class SmsSender
{
    public function __construct(private PopbillMessageService $popbill) {}

    /** 단건 발송. 성공 true / 실패·생략 false. */
    public function send(?string $to, string $content, ?string $name = null): bool
    {
        $to = preg_replace('/\D/', '', (string) $to);
        if ($to === '') {
            return false;
        }

        $cfg = config('popbill.sms', []);

        // 시뮬레이트: 실제 발송 없이 로그만
        if ($cfg['simulate'] ?? true) {
            Log::info('[SMS:simulate] to='.$to.($name ? " ({$name})" : '').' | '.$content);

            return true;
        }

        $sender = preg_replace('/\D/', '', (string) ($cfg['sender'] ?? ''));
        if ($sender === '') {
            Log::warning('[SMS] 발신번호(POPBILL_SMS_SENDER) 미설정 — 발송 생략');

            return false;
        }

        try {
            $this->popbill->sendXMS(
                preg_replace('/\D/', '', (string) ($cfg['corp_num'] ?? '')),
                $sender,
                null,
                $content,
                [['rcv' => $to, 'rcvnm' => $name ?: '']],
                $cfg['sender_name'] ?? null,
                ($cfg['user_id'] ?? null) ?: null,
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            Log::warning('[SMS] 발송 실패: '.$e->getMessage());

            return false;
        }
    }
}
