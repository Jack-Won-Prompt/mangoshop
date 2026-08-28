<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 하단 회사정보를 실제 사업자(메디셀 법인)와 동일하게 정렬 — 토스페이먼츠 심사용.
 * DB의 site 설정이 config/site.php 를 오버라이드하므로, 운영에서도 상호·이메일이 반영되도록 갱신.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'site')->first();
        if (! $row) {
            return; // 아직 DB 오버라이드가 없으면 config/site.php 값이 그대로 노출됨
        }
        $v = json_decode($row->value, true) ?: [];
        $v['company'] = '메디셀';
        $v['email'] = 'help@medisell.co.kr';

        DB::table('settings')->where('key', 'site')
            ->update(['value' => json_encode($v, JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        // 되돌리지 않음(회사정보는 사업자등록증 기준 고정)
    }
};
