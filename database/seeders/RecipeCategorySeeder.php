<?php

namespace Database\Seeders;

use App\Models\RecipeCategory;
use Illuminate\Database\Seeder;

/**
 * 레시피 커뮤니티 시작 카테고리 (idempotent · slug 기준 upsert).
 * 실행: php artisan db:seed --class=RecipeCategorySeeder
 */
class RecipeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => '망고',       'name' => '망고',        'tagline' => '애플망고·골드망고로 만드는 달콤한 레시피',        'meta' => '애플망고·골드망고 스무디, 빙수, 디저트 등 망고 레시피 모음'],
            ['slug' => '아보카도',   'name' => '아보카도',    'tagline' => '부드러운 아보카도 요리·샐러드·토스트',            'meta' => '아보카도 토스트, 샐러드, 과카몰리 등 아보카도 레시피 모음'],
            ['slug' => '열대과일',   'name' => '열대과일',    'tagline' => '두리안·용과·리치·망고스틴 등 이색 열대과일',        'meta' => '두리안·용과·리치·망고스틴 등 열대과일 활용 레시피 모음'],
            ['slug' => '시트러스',   'name' => '시트러스',    'tagline' => '오렌지·자몽·레몬으로 만드는 상큼한 레시피',         'meta' => '오렌지·자몽·레몬 청, 에이드, 디저트 등 시트러스 레시피 모음'],
            ['slug' => '파인애플',   'name' => '파인애플',    'tagline' => '새콤달콤 파인애플 활용 요리·음료',                'meta' => '파인애플 볶음밥, 에이드, 디저트 등 파인애플 레시피 모음'],
            ['slug' => '디저트음료', 'name' => '디저트·음료', 'tagline' => '스무디·빙수·케이크 등 과일 디저트와 음료',         'meta' => '과일 스무디·빙수·에이드·케이크 등 디저트와 음료 레시피 모음'],
        ];

        foreach ($rows as $i => $r) {
            RecipeCategory::updateOrCreate(
                ['slug' => $r['slug']],
                [
                    'name'             => $r['name'],
                    'tagline'          => $r['tagline'],
                    'meta_description' => $r['meta'],
                    'sort_order'       => $i + 1,
                    'is_active'        => true,
                ]
            );
            $this->command?->info("레시피 카테고리: {$r['name']}");
        }
    }
}
