<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillMessaging;

/**
 * 팝빌 문자(SMS/LMS) 얇은 래퍼 — SDK 지연 생성.
 */
class PopbillMessageService
{
    private ?PopbillMessaging $api = null;

    private function api(): PopbillMessaging
    {
        if ($this->api === null) {
            if (! defined('LINKHUB_COMM_MODE')) {
                define('LINKHUB_COMM_MODE', env('POPBILL_LINKHUB_COMM_MODE', 'CURL'));
            }
            $api = new PopbillMessaging(config('popbill.LinkID'), config('popbill.SecretKey'));
            $api->IsTest((bool) config('popbill.IsTest', true));
            $api->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
            $api->UseStaticIP((bool) config('popbill.UseStaticIP', false));
            $api->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
            $this->api = $api;
        }

        return $this->api;
    }

    /**
     * SMS/LMS 자동 발송(SendXMS: 내용 길이에 따라 자동 선택).
     *
     * @param  array  $messages  [['rcv'=>수신번호,'rcvnm'=>수신자명(선택)], ...]
     * @return string 접수번호(ReceiptNum)
     */
    public function sendXMS(string $corpNum, string $sender, ?string $subject, string $content, array $messages, ?string $senderName = null, ?string $userId = null): string
    {
        try {
            return $this->api()->SendXMS($corpNum, $sender, $subject, $content, $messages, null, false, $userId ?: null, $senderName);
        } catch (PopbillException $e) {
            throw new \RuntimeException('[팝빌문자 '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function getUnitCost(string $corpNum, string $messageType = 'XMS'): float
    {
        try {
            return (float) $this->api()->GetUnitCost($corpNum, $messageType);
        } catch (PopbillException $e) {
            throw new \RuntimeException('[팝빌문자 '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
