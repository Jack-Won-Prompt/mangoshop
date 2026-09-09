<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 레시피 질문(사용자가 물어보기)
        Schema::create('recipe_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipe_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('status')->default('published'); // published|hidden
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('answer_count')->default(0);
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // 답변(사용자 또는 관리자)
        Schema::create('recipe_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_admin')->default(false); // 관리자 답변 표시
            $table->string('status')->default('published');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_answers');
        Schema::dropIfExists('recipe_questions');
    }
};
