<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 망고샵 멀티벤더(오픈마켓) 확장
 * - sellers: 입점 수입사
 * - products: 신선식품/원산지/등급/MOQ/수량구간할인/판매상태/도매·소매가
 * - orders: 판매자(seller) 단위 분할 + 그룹 묶음
 * - user_addresses: 다중 배송지
 * - restock_alerts: 재입고 알림
 * - quotes/quote_items: 견적(RFQ)
 * - credit_accounts/credit_transactions: 도매 여신(후불/월결제)
 * - seller_settlements: 판매자 정산
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== 수입사(입점 판매자) =====
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            // 입점사 대표 로그인 계정 (판매자 콘솔 접근)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                         // 수입사명(상호)
            $table->string('slug')->unique();
            $table->string('biz_no')->nullable();           // 사업자등록번호
            $table->string('ceo_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->text('intro')->nullable();              // 수입사 소개
            $table->string('origin_focus')->nullable();     // 주력 원산지(예: 태국/베트남)
            // pending(입점심사) / approved / suspended
            $table->string('status')->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(10.00); // 플랫폼 수수료율(%)
            $table->unsignedInteger('shipping_fee')->default(3000);   // 기본 배송비
            $table->unsignedInteger('free_shipping_threshold')->nullable(); // 무료배송 기준액
            $table->boolean('coldchain')->default(true);    // 콜드체인 배송
            $table->text('shipping_notice')->nullable();    // 배송 안내/불가지역
            $table->unsignedInteger('rating_sum')->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ===== 상품: 멀티벤더 + 신선식품 속성 =====
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('id')->constrained('sellers')->nullOnDelete();
            $table->string('origin')->nullable()->after('maker');       // 원산지
            $table->string('variety')->nullable()->after('origin');     // 품종(애플망고/옐로우 등)
            $table->string('grade')->nullable()->after('variety');      // 등급
            $table->string('box_spec')->nullable()->after('grade');     // 박스규격(예: 5kg/6과)
            $table->decimal('weight_kg', 8, 2)->nullable()->after('box_spec'); // 중량(kg)
            $table->date('inbound_date')->nullable()->after('weight_kg');   // 입고일
            $table->date('expiry_date')->nullable()->after('inbound_date'); // 유통기한
            $table->string('storage_method')->nullable()->after('expiry_date'); // 보관방법
            $table->string('lot_no')->nullable()->after('storage_method');      // 로트번호

            $table->unsignedInteger('wholesale_price')->nullable()->after('member_price'); // 도매회원가
            $table->unsignedInteger('moq')->default(1)->after('wholesale_price');          // 최소주문수량
            $table->json('price_tiers')->nullable()->after('moq'); // 수량구간 할인 [{min_qty, price}]
            // on_sale / soldout / closed(당일마감) / inbound(입고예정)
            $table->string('sale_status')->default('on_sale')->after('is_active');
            $table->date('expected_inbound_date')->nullable()->after('sale_status'); // 입고예정일
            $table->unsignedInteger('sales_count')->default(0)->after('view_count');  // 판매량(정렬용)
        });

        // ===== 주문: 판매자 단위 분할 + 그룹 =====
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_group_no')->nullable()->after('order_no')->index(); // 한 번의 결제(여러 수입사 묶음)
            $table->foreignId('seller_id')->nullable()->after('user_id')->constrained('sellers')->nullOnDelete();
            $table->date('desired_delivery_date')->nullable()->after('memo'); // 희망 배송일
            $table->boolean('is_credit')->default(false)->after('total');     // 여신(후불) 결제 여부
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('order_id')->constrained('sellers')->nullOnDelete();
        });

        // ===== 다중 배송지 =====
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();     // 배송지 별칭(예: 매장, 창고)
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->string('postcode', 10)->nullable();
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // ===== 재입고 알림 =====
        Schema::create('restock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        // ===== 견적(RFQ) =====
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_no')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            // requested(요청) / quoted(회신) / accepted(수락) / rejected(거절) / expired / ordered
            $table->string('status')->default('requested');
            $table->string('title')->nullable();
            $table->text('memo')->nullable();          // 구매자 요청사항
            $table->text('seller_memo')->nullable();   // 판매자 회신 메모
            $table->date('desired_date')->nullable();  // 희망 납품일
            $table->unsignedInteger('estimate_total')->default(0); // 회신 견적 합계
            $table->date('valid_until')->nullable();   // 견적 유효기한
            $table->timestamp('quoted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('requested_price')->nullable(); // 구매자 희망가
            $table->unsignedInteger('quoted_price')->nullable();    // 판매자 회신 단가
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // ===== 도매 여신(후불/월결제) =====
        Schema::create('credit_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('limit_amount')->default(0); // 여신 한도
            $table->bigInteger('used_amount')->default(0);          // 사용(미상환) 금액
            $table->string('terms')->default('net30');              // net30 / monthly
            $table->string('status')->default('active');            // active / suspended
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');            // charge(여신사용) / repay(상환)
            $table->bigInteger('amount');
            $table->bigInteger('balance');     // 변동 후 미상환 잔액
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        // ===== 판매자 정산 =====
        Schema::create('seller_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('gross_amount')->default(0);     // 판매액
            $table->unsignedInteger('commission_amount')->default(0); // 수수료
            $table->integer('net_amount')->default(0);               // 정산액
            $table->string('status')->default('pending'); // pending / settled
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_settlements');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('credit_accounts');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('restock_alerts');
        Schema::dropIfExists('user_addresses');

        Schema::table('order_items', fn (Blueprint $t) => $t->dropConstrainedForeignId('seller_id'));
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('seller_id');
            $t->dropColumn(['order_group_no', 'desired_delivery_date', 'is_credit']);
        });
        Schema::table('products', function (Blueprint $t) {
            $t->dropConstrainedForeignId('seller_id');
            $t->dropColumn([
                'origin', 'variety', 'grade', 'box_spec', 'weight_kg',
                'inbound_date', 'expiry_date', 'storage_method', 'lot_no',
                'wholesale_price', 'moq', 'price_tiers', 'sale_status',
                'expected_inbound_date', 'sales_count',
            ]);
        });
        Schema::dropIfExists('sellers');
    }
};
