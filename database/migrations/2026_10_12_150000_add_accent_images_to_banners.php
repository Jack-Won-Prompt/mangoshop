<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // 히어로 슬라이드의 작은 원형 포인트 이미지(비우면 기본 이미지 자동 대체)
            $table->string('acc1')->nullable()->after('image');
            $table->string('acc2')->nullable()->after('acc1');
            $table->string('acc3')->nullable()->after('acc2');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['acc1', 'acc2', 'acc3']);
        });
    }
};
