<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 레시피 카테고리
        Schema::create('recipe_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();      // 한 줄 설명(목록/헤더)
            $table->string('cover')->nullable();         // 카테고리 대표 이미지
            $table->text('description')->nullable();     // 상단 소개(SEO 본문)
            $table->string('meta_title')->nullable();    // SEO
            $table->string('meta_description', 300)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 레시피 글(공식=user_id null / 사용자 글=user_id 존재)
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug', 200);                 // 한글 허용, 카테고리 내 유니크
            $table->string('summary', 300)->nullable();
            $table->longText('body')->nullable();        // 리치 HTML(글+사진)
            $table->string('cover_image')->nullable();   // 대표 이미지(목록/OG)
            $table->boolean('is_official')->default(false); // 관리자 공식 레시피
            $table->boolean('is_pinned')->default(false);   // 카테고리 상단 고정
            $table->string('status')->default('published'); // published|pending|hidden
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            // 선택: 구조화 데이터(schema.org/Recipe)용
            $table->string('cook_time')->nullable();     // 예: PT20M 또는 "20분"
            $table->text('ingredients')->nullable();     // 줄바꿈 구분
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['recipe_category_id', 'slug']);
            $table->index(['status', 'is_pinned', 'published_at']);
        });

        // 레시피 갤러리 이미지(다중)
        Schema::create('recipe_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_images');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('recipe_categories');
    }
};
