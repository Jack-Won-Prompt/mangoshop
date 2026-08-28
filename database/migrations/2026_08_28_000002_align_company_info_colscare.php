<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 하단 회사정보를 실제 운영법인(주식회사 링크더랩 · colscare.com)과 동일하게 정렬 — 토스페이먼츠 심사용.
 * DB의 site 설정이 config/site.php 를 오버라이드하므로 운영에서도 반영되도록 갱신.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'site')->first();
        if (! $row) {
            return;
        }
        $v = json_decode($row->value, true) ?: [];

        $v['company']   = '주식회사 링크더랩';
        $v['ceo']       = '최연아';
        $v['biz_no']    = '490-86-01851';
        $v['mailorder'] = '제2021-서울강서-2026호';
        $v['address']   = '서울특별시 영등포구 경인로77길 49, 109동 2층 201-60호 (문래동4가, 리버뷰 신안인스빌)';
        $v['cs_tel']    = '02-1544-9086';
        $v['email']     = 'admin@colscare.com';
        $v['banks']     = [
            ['bank' => '국민은행', 'account' => '834701-04-159739', 'holder' => '주식회사 링크더랩'],
        ];

        DB::table('settings')->where('key', 'site')
            ->update(['value' => json_encode($v, JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        // 되돌리지 않음(회사정보는 사업자등록증 기준 고정)
    }
};
