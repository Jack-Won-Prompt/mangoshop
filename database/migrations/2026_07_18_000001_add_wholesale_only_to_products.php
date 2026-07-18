<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 도매 전용 상품 플래그.
 * true 이면 비로그인/미승인 회원에게 가격을 숨김("회원 승인 후 가격 확인").
 * 기본 false → 소매가는 누구에게나 공개(exfresh 방식).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('wholesale_only')->default(false)->after('wholesale_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('wholesale_only'));
    }
};
