<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sr_threads', function (Blueprint $table) {
            // none | requested | done  — 개발 반영 요청 상태(실행 아님, 담당자 처리용 표시)
            $table->string('apply_status', 16)->default('none')->after('model');
            $table->text('apply_note')->nullable()->after('apply_status');
            $table->timestamp('apply_requested_at')->nullable()->after('apply_note');
        });
    }

    public function down(): void
    {
        Schema::table('sr_threads', function (Blueprint $table) {
            $table->dropColumn(['apply_status', 'apply_note', 'apply_requested_at']);
        });
    }
};
