<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('image')->nullable();          // 상대경로 또는 http(s) 절대주소
            $table->string('link')->nullable();           // 클릭 시 이동 주소
            $table->string('button')->nullable();         // CTA 문구(비우면 버튼 없음)
            $table->boolean('new_window')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        // 기존 config('site.popups') 기본 팝업을 1회 이관(최초 생성 시에만)
        $seed = config('site.popups', []);
        $now = now();
        foreach (array_values($seed) as $i => $p) {
            if (empty($p['image'])) {
                continue;
            }
            DB::table('popups')->insert([
                'title'      => $p['title'] ?? null,
                'image'      => $p['image'],
                'link'       => $p['link'] ?? null,
                'button'     => $p['button'] ?? null,
                'new_window' => (bool) ($p['new_window'] ?? false),
                'is_active'  => (bool) ($p['enabled'] ?? true),
                'sort_order' => $i,
                'start_date' => $p['start'] ?? null,
                'end_date'   => $p['end'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('popups');
    }
};
