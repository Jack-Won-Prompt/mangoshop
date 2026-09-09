<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Models\RecipeQuestion;
use Illuminate\Console\Command;

/**
 * 레시피/질문의 이미지를 제목(과일 키워드)에 맞게 재매핑한다.
 * 실행: php artisan mangoshop:recipe-remap-images
 */
class RecipeRemapImages extends Command
{
    protected $signature = 'mangoshop:recipe-remap-images';

    protected $description = '레시피/질문 이미지를 내용(과일)에 맞게 재매핑';

    /** 과일 키워드 → 이미지 (구체적 키워드가 먼저) */
    public const MAP = [
        '골드망고' => ['mango-fruit-3.jpg', 'mango-fruit-0.jpg'],
        '애플망고' => ['mango-fruit-1.jpg', 'mango-nam-dok-mai-1.jpg'],
        '남독마이' => ['mango-nam-dok-mai-0.jpg', 'mango-nam-dok-mai-1.jpg'],
        '그린망고' => ['green-mango-0.jpg'],
        '청망고'   => ['green-mango-0.jpg'],
        '망고스틴' => ['mangosteen-0.jpg'],
        '망고'     => ['mango-fruit-0.jpg', 'mango-fruit-1.jpg', 'mango-fruit-2.jpg', 'mango-nam-dok-mai-0.jpg'],
        '아보카도' => ['avocado-fruit-0.jpg'],
        '두리안'   => ['durian-fruit-0.jpg'],
        '리치'     => ['lychee-fruit-0.jpg'],
        '용과'     => ['pitaya-0.jpg'],
        '드래곤'   => ['pitaya-0.jpg'],
        '자몽'     => ['grapefruit-0.jpg'],
        '오렌지'   => ['orange-fruit-0.jpg'],
        '레몬'     => ['orange-fruit-0.jpg'],
        '시트러스' => ['orange-fruit-0.jpg', 'grapefruit-0.jpg'],
        '파인애플' => ['pineapple-fruit-0.jpg'],
    ];

    /** 요리별 실제 사진(과일 매핑보다 우선) → images/recipe/ */
    public const DISH_MAP = [
        '파인애플 새우볶음' => ['pine-shrimp-1.jpg', 'pine-shrimp-2.jpg', 'pine-shrimp-3.jpg'],
        '망고 빙수'         => ['mango-bingsu-1.jpg'],
    ];

    /** 카테고리 slug → 기본 이미지(제목에 과일 없을 때) */
    public const CAT_DEFAULT = [
        '망고' => ['mango-fruit-0.jpg', 'mango-fruit-2.jpg'],
        '아보카도' => ['avocado-fruit-0.jpg'],
        '열대과일' => ['durian-fruit-0.jpg', 'lychee-fruit-0.jpg', 'mangosteen-0.jpg', 'pitaya-0.jpg'],
        '시트러스' => ['orange-fruit-0.jpg', 'grapefruit-0.jpg'],
        '파인애플' => ['pineapple-fruit-0.jpg'],
        '디저트음료' => ['tropical-fruit-market-0.jpg', 'mango-fruit-2.jpg', 'orange-fruit-0.jpg', 'pineapple-fruit-0.jpg'],
    ];

    public static function poolFor(string $title, ?string $catSlug): array
    {
        foreach (self::MAP as $kw => $imgs) {
            if (mb_strpos($title, $kw) !== false) {
                return $imgs;
            }
        }

        return self::CAT_DEFAULT[$catSlug] ?? ['tropical-fruit-market-0.jpg'];
    }

    /** 제목 기반 대표 이미지(안정적 선택) — 요리별 실제 사진 우선 */
    public static function pick(string $title, ?string $catSlug, int $i = 0): string
    {
        foreach (self::DISH_MAP as $kw => $imgs) {
            if (mb_strpos($title, $kw) !== false) {
                $idx = ($i > 0) ? ($i % count($imgs)) : (abs(crc32($title)) % count($imgs));

                return 'images/recipe/'.$imgs[$idx];
            }
        }
        $pool = self::poolFor($title, $catSlug);
        $idx = ($i > 0) ? ($i % count($pool)) : (abs(crc32($title)) % count($pool));

        return 'images/fruit/'.$pool[$idx];
    }

    public function handle(): int
    {
        $r1 = 0;
        Recipe::with('category', 'images')->chunkById(200, function ($recipes) use (&$r1) {
            foreach ($recipes as $r) {
                $slug = $r->category->slug ?? null;
                $r->cover_image = self::pick($r->title, $slug, 0);
                $r->saveQuietly();
                foreach ($r->images as $k => $img) {
                    $img->path = self::pick($r->title, $slug, $k + 1);
                    $img->saveQuietly();
                }
                $r1++;
            }
        });

        $q1 = 0;
        RecipeQuestion::with('category', 'images')->chunkById(200, function ($questions) use (&$q1) {
            foreach ($questions as $q) {
                $slug = $q->category->slug ?? null;
                foreach ($q->images as $k => $img) {
                    $img->path = self::pick($q->title, $slug, $k + 1);
                    $img->saveQuietly();
                }
                $q1++;
            }
        });

        $this->info("재매핑 완료: 레시피 {$r1}건, 질문 {$q1}건");

        return self::SUCCESS;
    }
}
