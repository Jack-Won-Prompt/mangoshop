<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 회원 마케팅 정보 수신 동의(선택).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'marketing_agree')) {
                $table->boolean('marketing_agree')->default(false)->after('point');
            }
            if (! Schema::hasColumn('users', 'marketing_agreed_at')) {
                $table->timestamp('marketing_agreed_at')->nullable()->after('marketing_agree');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['marketing_agree', 'marketing_agreed_at'] as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
