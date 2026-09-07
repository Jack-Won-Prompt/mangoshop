<?php

namespace App\Support;

use App\Models\User;
use App\Services\FcmService;

/**
 * 관리자 앱으로 FCM 푸시를 보내는 공통 헬퍼.
 * is_admin 사용자 전체를 대상으로 하며, 실패는 삼켜서 본 흐름을 막지 않는다.
 */
class AdminPush
{
    public static function toAdmins(string $title, string $body, array $data = []): void
    {
        try {
            $ids = User::where('is_admin', true)->pluck('id');
            if ($ids->isEmpty()) {
                return;
            }
            app(FcmService::class)->sendToUsers($ids, $title, $body, $data);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
