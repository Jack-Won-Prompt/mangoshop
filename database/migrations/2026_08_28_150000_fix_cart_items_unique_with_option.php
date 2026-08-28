<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 장바구니 유니크 제약을 (user_id, product_id) → (user_id, product_id, option_id) 로 교체.
 * 같은 상품이라도 옵션이 다르면 별도 라인으로 담을 수 있도록(중복키 오류 해결).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 새 유니크를 먼저 추가(user_id 로 시작 → user_id FK 인덱스 유지) 후 기존 제거
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id', 'option_id'], 'cart_items_user_product_option_unique');
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_id_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_product_option_unique');
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id'], 'cart_items_user_id_product_id_unique');
        });
    }
};
