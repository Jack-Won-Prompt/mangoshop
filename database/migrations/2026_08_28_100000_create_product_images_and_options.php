<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 상품 관리(등록/수정) 고도화 — korsafety 동일 기능:
 *  - product_images: 갤러리/상세 이미지(type=gallery|detail, sort)
 *  - product_options: 단일레벨 옵션(옵션구분/선택지/추가금액/재고/활성/정렬)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('path');
                $table->string('type', 20)->default('gallery'); // gallery | detail
                $table->unsignedInteger('sort')->default(0);
                $table->index(['product_id', 'type', 'sort']);
            });
        }

        if (! Schema::hasTable('product_options')) {
            Schema::create('product_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('group_name', 60)->nullable();     // 옵션 구분(예: 규격)
                $table->string('name', 120);                       // 선택지(예: 5kg 박스)
                $table->integer('extra_price')->default(0);        // 추가 금액(±)
                $table->unsignedInteger('stock')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->index(['product_id', 'sort']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_images');
    }
};
