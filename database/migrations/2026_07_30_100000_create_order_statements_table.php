<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seq')->default(1);          // 재발행 회차
            $table->string('file_name');
            $table->date('statement_date')->nullable();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('action', 20)->default('download');   // download | email
            $table->string('sent_to')->nullable();               // 이메일 수신자
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_statements');
    }
};
