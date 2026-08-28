<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 메인 '새로 들어온 과일' 섹션 제목 초기값을 추석 명절 문구로 설정(관리자에서 이후 변경 가능).
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
        $v['home_new_title'] = '2026년 추석 명절 선물셋트';

        DB::table('settings')->where('key', 'site')
            ->update(['value' => json_encode($v, JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        // 되돌리지 않음(관리자에서 변경)
    }
};
