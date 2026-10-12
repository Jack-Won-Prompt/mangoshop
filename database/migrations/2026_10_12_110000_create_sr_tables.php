<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sr_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->default('새 요청');
            $table->string('model')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index(['last_message_at']);
        });

        Schema::create('sr_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sr_thread_id')->constrained('sr_threads')->cascadeOnDelete();
            $table->string('role', 16);                 // user | assistant
            $table->longText('content')->nullable();
            $table->json('attachments')->nullable();    // [{path,name,mime,size}]
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->timestamps();
            $table->index(['sr_thread_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sr_messages');
        Schema::dropIfExists('sr_threads');
    }
};
