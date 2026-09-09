<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_questions', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('body'); // 유튜브 등 영상 링크
        });

        Schema::create('recipe_question_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_question_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_question_images');
        Schema::table('recipe_questions', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
};
