<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 12)->default('server');   // server | client
            $table->string('fingerprint', 64)->index();        // 동일 오류 그룹핑
            $table->string('exception')->nullable();
            $table->text('message')->nullable();
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_agent', 400)->nullable();
            $table->longText('trace')->nullable();
            $table->unsignedInteger('count')->default(1);
            $table->foreignId('sr_thread_id')->nullable()->constrained('sr_threads')->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['resolved_at', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_events');
    }
};
