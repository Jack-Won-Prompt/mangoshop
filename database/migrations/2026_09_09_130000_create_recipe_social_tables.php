<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 좋아요
        Schema::create('recipe_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['recipe_id', 'user_id']);
        });

        // 댓글
        Schema::create('recipe_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_admin')->default(false);
            $table->string('status')->default('published'); // published|hidden
            $table->timestamps();
        });

        // 태그(콤마 구분) + 댓글수
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('tags')->nullable()->after('ingredients');
            $table->unsignedInteger('comment_count')->default(0)->after('like_count');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['tags', 'comment_count']);
        });
        Schema::dropIfExists('recipe_comments');
        Schema::dropIfExists('recipe_likes');
    }
};
