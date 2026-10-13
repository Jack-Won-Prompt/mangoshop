<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 자동 처리 Agent 점검용 임시 칸.
 * 표 구조 변경이 받아두기·되돌리기와 함께 제대로 도는지 확인하려고 잠깐 넣는다.
 * 확인이 끝나면 지운다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sr_threads', function (Blueprint $table) {
            // 이미 줄이 들어 있으므로 비워 둘 수 있게 한다
            $table->string('agent_selftest', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sr_threads', function (Blueprint $table) {
            $table->dropColumn('agent_selftest');
        });
    }
};
