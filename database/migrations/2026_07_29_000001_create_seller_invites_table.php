<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 수입사(판매자) 입점 초대.
 * - 관리자가 이메일로 초대 → 초대 링크(토큰)로 입점 신청/개설 → 초대 accepted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_invites', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('company_name')->nullable();   // 제안 수입사명
            $table->string('origin_focus')->nullable();    // 주력 원산지
            $table->string('token', 64)->unique();
            $table->string('status')->default('pending');  // pending | accepted | revoked
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_invites');
    }
};
