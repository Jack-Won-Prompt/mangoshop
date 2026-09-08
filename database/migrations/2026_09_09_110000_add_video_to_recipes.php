<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('cover_image');   // 유튜브/외부 영상 링크
            $table->string('video_path')->nullable()->after('video_url');    // 업로드 영상 파일
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'video_path']);
        });
    }
};
