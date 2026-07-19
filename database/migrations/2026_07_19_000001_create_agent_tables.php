<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 구매 대행자(Agent) 도메인.
 * - 구매 대행자: 여러 구매자(소매처)를 대신해 주문. users.is_agent + cashback_rate.
 * - agent_buyers: 대행자가 관리하는 구매자(소매처) 명부 (이름/사업자번호/전화).
 * - orders: 어떤 대행자가 어떤 구매자를 위해 주문했는지 스냅샷 + 캐시백액.
 * - agent_cashbacks: 주문 1건당 대행자에게 지급되는 캐시백 원장.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_agent')->default(false)->after('is_admin');
            // 캐시백율(%) — 대행자가 주문할 때 주문금액의 이 비율만큼 캐시백
            $table->decimal('cashback_rate', 5, 2)->default(0)->after('is_agent');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('buyer_name')->nullable()->after('agent_id');      // 구매자(소매처) 이름
            $table->string('buyer_biz_no')->nullable()->after('buyer_name');  // 구매자 사업자번호
            $table->string('buyer_phone')->nullable()->after('buyer_biz_no'); // 구매자 전화번호
            $table->unsignedInteger('cashback_amount')->default(0)->after('buyer_phone');
        });

        // 대행자가 관리하는 구매자(소매처) 명부
        Schema::create('agent_buyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('biz_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        // 캐시백 원장 (주문 1건 = 1행)
        Schema::create('agent_cashbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('buyer_name')->nullable();
            $table->unsignedInteger('order_amount');           // 캐시백 산정 기준 주문금액
            $table->decimal('rate', 5, 2);                     // 적용 캐시백율(%)
            $table->unsignedInteger('amount');                 // 캐시백 지급액
            $table->string('status')->default('paid');         // paid(적립완료) / pending / cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_cashbacks');
        Schema::dropIfExists('agent_buyers');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
            $table->dropColumn(['buyer_name', 'buyer_biz_no', 'buyer_phone', 'cashback_amount']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_agent', 'cashback_rate']);
        });
    }
};
