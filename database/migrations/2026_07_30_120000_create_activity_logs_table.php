<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 방문이력(통합 활동로그) — 웹/앱 방문(페이지 접속)·상품검색·상품조회·로그인 이력을 한 곳에 기록.
 * 비로그인(게스트)은 session_id 로 추적한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();               // visit | search | product_view | login
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->nullable();       // 게스트 추적
            $table->string('platform', 10)->default('web');     // web | app
            $table->string('path', 512)->nullable();            // 방문 경로/URL
            $table->string('keyword', 191)->nullable();         // 검색어
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 191)->nullable();    // 조회 상품명 스냅샷
            $table->unsignedInteger('result_count')->nullable();// 검색 결과 수
            $table->string('email', 191)->nullable();           // 로그인 시도 이메일
            $table->string('status', 20)->nullable();           // 로그인: success | fail
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 512)->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
