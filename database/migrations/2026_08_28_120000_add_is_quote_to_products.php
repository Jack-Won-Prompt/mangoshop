<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 가격문의 상품 — 가격을 숨기고 '가격문의(견적문의)'로 노출.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_quote')) {
                $table->boolean('is_quote')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_quote')) {
                $table->dropColumn('is_quote');
            }
        });
    }
};
