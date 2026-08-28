<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 상품 옵션 → 장바구니/주문 연동:
 *  - cart_items.option_id : 선택 옵션(같은 상품이라도 옵션이 다르면 별도 라인)
 *  - order_items.option_id/option_name/option_extra : 주문 시점 옵션 스냅샷
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'option_id')) {
                $table->foreignId('option_id')->nullable()->after('product_id')
                    ->constrained('product_options')->nullOnDelete();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'option_id')) {
                $table->unsignedBigInteger('option_id')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('order_items', 'option_name')) {
                $table->string('option_name', 160)->nullable()->after('product_name');
            }
            if (! Schema::hasColumn('order_items', 'option_extra')) {
                $table->integer('option_extra')->default(0)->after('option_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'option_id')) {
                $table->dropConstrainedForeignId('option_id');
            }
        });
        Schema::table('order_items', function (Blueprint $table) {
            foreach (['option_id', 'option_name', 'option_extra'] as $c) {
                if (Schema::hasColumn('order_items', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
