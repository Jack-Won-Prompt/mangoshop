<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * 수입 프리미엄 상품 · 선물세트 등록 시더 (idempotent).
 *  - 파키스탄 망고(orchard&co) + 프리미엄 망고 선물세트 5종
 *  - code 기준 updateOrCreate → 재실행해도 안전, 이미지 경로는 배포 호스트에서 Media 정규화
 *  - category 는 slug 로 해석하여 운영/로컬 카테고리 ID가 달라도 매핑됨
 *
 * 실행: php artisan db:seed --class=ImportedProductSeeder
 */
class ImportedProductSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
  0 => 
  array (
    'code' => 'ORD-PKMANGO-4KG',
    'category_slug' => 'yellow-mango',
    'name' => '핵당도 파키스탄 망고 4kg (원물 3.8kg 내외)',
    'slug' => 'pakistan-mango-4kg',
    'unit' => 'BOX',
    'maker' => 'orchard&co',
    'summary' => 'BRIX 22± 초고당도 · 전량 항공직수입 · 섬유질 없는 부드러운 과육',
    'description' => '<div class="prod-detail">
  <p style="font-size:15px;line-height:1.8;color:#3d2a12">
    <b>orchard&amp;co</b> 파키스탄 망고를 항공 직수입으로 만나보세요.
    파키스탄은 세계적으로 <b>‘망고의 나라’</b>로 유명한 최상위권 망고 산지입니다.
    파키스탄 망고는 섬유질이 거의 없어 입안에서 사르르 녹아내리는 과육과,
    <b>BRIX 22± 초고당도</b>(상급 개체는 25 Brix 이상), 진한 향, 부드러운 식감으로 <b>‘망고의 왕’</b>이라 불립니다.
    맛있게 익는 숙도를 계산해 <b>전량 항공 직수입</b>하며, 만족하지 못하면 판매하지 않는다는 기준으로 선별합니다.
  </p>
  <ul style="font-size:14px;line-height:1.9;color:#5a4a30;margin:14px 0 22px;padding-left:18px">
    <li>원산지 : 파키스탄 (항공 직수입 · AIR MANGO)</li>
    <li>당도 : BRIX 22± (상급 25 Brix 이상)</li>
    <li>규격 : 4kg 1박스(박스 무게 포함) · 원물 무게 3.8kg 내외(±200g)</li>
    <li>특징 : 섬유질 거의 없는 부드러운 과육 · 진한 향과 풍부한 맛 · 연유맛/요구르트맛</li>
    <li>※ 생물 특성상 개체별 크기와 무게, 포장 개수는 달라질 수 있습니다.</li>
  </ul>
  <img src="images/products/orchardnco/detail-01.jpg" alt="파키스탄 망고 - 오직 압도적인 달콤함" style="width:100%;height:auto;display:block">
  <img src="images/products/orchardnco/detail-02.jpg" alt="핵당도 오차드 파키스탄 망고 착륙" style="width:100%;height:auto;display:block">
  <img src="images/products/orchardnco/detail-03.jpg" alt="25Brix를 넘나드는 초고당도" style="width:100%;height:auto;display:block">
  <img src="images/products/orchardnco/detail-04.jpg" alt="직접 비교해봤습니다 · 지금이 가장 좋은 시기" style="width:100%;height:auto;display:block">
  <img src="images/products/orchardnco/detail-05.jpg" alt="왜 오차드 파키스탄 망고일까요 - 선별/검증" style="width:100%;height:auto;display:block">
  <img src="images/products/orchardnco/detail-06.jpg" alt="가장 맛있는 숙도 계산 · 전량 항공 직수입" style="width:100%;height:auto;display:block">
  <img src="images/products/orchardnco/detail-07.jpg" alt="오차드의 선별 기준 · 만족하지 못하면 판매하지 않습니다" style="width:100%;height:auto;display:block">
  <img src="images/products/orchardnco/detail-08.jpg" alt="4kg 1박스(박스 포함) · 원물 무게 3.8kg 내외" style="width:100%;height:auto;display:block">
</div>
',
    'origin' => '파키스탄',
    'variety' => '파키스탄 망고 (항공직수입)',
    'grade' => '프리미엄 (BRIX 22±)',
    'box_spec' => '4kg/1박스(박스포함) · 원물 3.8kg 내외',
    'weight_kg' => '3.80',
    'moq' => 1,
    'stock' => 100,
    'tax_type' => 'exempt',
    'price' => 0,
    'wholesale_price' => 0,
    'member_price' => NULL,
    'thumbnail' => 'images/products/orchardnco/main-01.jpg',
    'images' => 
    array (
      0 => 'images/products/orchardnco/main-02.jpg',
      1 => 'images/products/orchardnco/main-03.jpg',
      2 => 'images/products/orchardnco/main-04.jpg',
    ),
    'sale_status' => 'on_sale',
    'is_new' => true,
    'is_best' => false,
    'is_featured' => false,
    'is_active' => false,
  ),
  1 => 
  array (
    'code' => 'GSP001',
    'category_slug' => 'giftset',
    'name' => '프리미엄 애플망고 선물세트 (8과·9과 선택)',
    'slug' => 'premium-apple-mango-giftset',
    'unit' => 'BOX',
    'maker' => 'Fresh Fresh',
    'summary' => '달콤함과 향이 뛰어난 프리미엄 애플망고 · 8과/9과 선택 · 고급 선물박스',
    'description' => '<div class="prod-detail">
  <p style="font-size:15px;line-height:1.85;color:#3d2a12">달콤함과 향이 뛰어난 <b>프리미엄 애플망고</b>를 정성껏 담았습니다. 붉게 물든 탐스러운 빛깔과 부드럽고 진한 과육이 특징이며, 소중한 분께 마음을 전하기 좋은 고급 선물세트입니다.</p>
  <ul style="font-size:14px;line-height:1.95;color:#5a4a30;margin:14px 0 6px;padding-left:18px"><li>구성 : 프리미엄 애플망고 <b>8과</b> 또는 <b>9과</b> 선택</li><li>가격 : 8과 58,900원 / 9과 65,000원</li><li>포장 : 고급 선물박스 + 개별 완충 포장</li></ul>
  <div class="gift-note" style="background:#fff6ef;border:1px solid #ffe0c4;border-radius:12px;padding:16px 18px;margin:16px 0;font-size:14px;line-height:1.9;color:#5a4a30">
  <b style="color:#c9640a">🎁 선물 포장 안내</b><br>
  · 하나하나 완충망으로 개별 포장하여 신선함 그대로 안전하게 배송합니다.<br>
  · <b>선물가방 또는 보자기 포장</b> 선택 시 <b>2,500원</b>이 추가됩니다.<br>
  · 생물 특성상 개체별 크기·무게 및 포장 개수는 다소 달라질 수 있습니다.
</div>
<img src="images/giftset/orchard/detail-options.jpg" alt="상품 구성 및 가격 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
<img src="images/giftset/orchard/detail-packaging.jpg" alt="마음을 전하는 신선한 망고 선물 · 포장 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
</div>',
    'origin' => '수입산',
    'variety' => '애플망고',
    'grade' => '프리미엄',
    'box_spec' => '8과 58,900원 / 9과 65,000원 (선택)',
    'weight_kg' => NULL,
    'moq' => 1,
    'stock' => 100,
    'tax_type' => 'exempt',
    'price' => 58900,
    'wholesale_price' => NULL,
    'member_price' => NULL,
    'thumbnail' => 'images/giftset/orchard/applemango-main.jpg',
    'images' => 
    array (
      0 => 'images/giftset/orchard/applemango-real-9.jpg',
      1 => 'images/giftset/orchard/applemango-box6.jpg',
      2 => 'images/giftset/orchard/box-closed.jpg',
    ),
    'sale_status' => 'on_sale',
    'is_new' => true,
    'is_best' => false,
    'is_featured' => false,
    'is_active' => true,
  ),
  2 => 
  array (
    'code' => 'GSP002',
    'category_slug' => 'giftset',
    'name' => '프리미엄 골드망고 선물세트 (8과·9과 선택)',
    'slug' => 'premium-gold-mango-giftset',
    'unit' => 'BOX',
    'maker' => 'Fresh Fresh',
    'summary' => '부드러운 식감과 풍부한 과즙의 프리미엄 골드망고 · 8과/9과 선택 · 고급 선물박스',
    'description' => '<div class="prod-detail">
  <p style="font-size:15px;line-height:1.85;color:#3d2a12">부드러운 식감과 풍부한 과즙의 <b>프리미엄 골드망고</b>를 담았습니다. 섬유질이 적어 부드럽게 넘어가는 과육과 진한 단맛으로, 남녀노소 누구나 좋아하는 프리미엄 선물세트입니다.</p>
  <ul style="font-size:14px;line-height:1.95;color:#5a4a30;margin:14px 0 6px;padding-left:18px"><li>구성 : 프리미엄 골드망고 <b>8과</b> 또는 <b>9과</b> 선택</li><li>가격 : 8과 50,500원 / 9과 55,500원</li><li>포장 : 고급 선물박스 + 개별 완충 포장</li></ul>
  <div class="gift-note" style="background:#fff6ef;border:1px solid #ffe0c4;border-radius:12px;padding:16px 18px;margin:16px 0;font-size:14px;line-height:1.9;color:#5a4a30">
  <b style="color:#c9640a">🎁 선물 포장 안내</b><br>
  · 하나하나 완충망으로 개별 포장하여 신선함 그대로 안전하게 배송합니다.<br>
  · <b>선물가방 또는 보자기 포장</b> 선택 시 <b>2,500원</b>이 추가됩니다.<br>
  · 생물 특성상 개체별 크기·무게 및 포장 개수는 다소 달라질 수 있습니다.
</div>
<img src="images/giftset/orchard/detail-options.jpg" alt="상품 구성 및 가격 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
<img src="images/giftset/orchard/detail-packaging.jpg" alt="마음을 전하는 신선한 망고 선물 · 포장 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
</div>',
    'origin' => '수입산',
    'variety' => '골드망고',
    'grade' => '프리미엄',
    'box_spec' => '8과 50,500원 / 9과 55,500원 (선택)',
    'weight_kg' => NULL,
    'moq' => 1,
    'stock' => 100,
    'tax_type' => 'exempt',
    'price' => 50500,
    'wholesale_price' => NULL,
    'member_price' => NULL,
    'thumbnail' => 'images/giftset/orchard/goldmango-main.jpg',
    'images' => 
    array (
      0 => 'images/giftset/orchard/goldmango-real-9.jpg',
      1 => 'images/giftset/orchard/goldmango-8.jpg',
      2 => 'images/giftset/orchard/goldmango-9.jpg',
    ),
    'sale_status' => 'on_sale',
    'is_new' => true,
    'is_best' => false,
    'is_featured' => false,
    'is_active' => true,
  ),
  3 => 
  array (
    'code' => 'GSP003',
    'category_slug' => 'giftset',
    'name' => '애플망고 선물박스 (2kg 내외·3~6과)',
    'slug' => 'apple-mango-giftbox-2kg',
    'unit' => 'BOX',
    'maker' => 'Fresh Fresh',
    'summary' => '탐스러운 애플망고 2kg 내외(3~6과) · 오렌지 선물박스',
    'description' => '<div class="prod-detail">
  <p style="font-size:15px;line-height:1.85;color:#3d2a12">붉게 익은 <b>애플망고</b>를 부담 없는 구성으로 담은 선물박스입니다. 2kg 내외(3~6과)로, 가볍게 마음을 전하기 좋은 실속형 선물세트입니다.</p>
  <ul style="font-size:14px;line-height:1.95;color:#5a4a30;margin:14px 0 6px;padding-left:18px"><li>구성 : 애플망고 <b>2kg 내외 (3~6과)</b></li><li>가격 : 37,000원</li><li>포장 : 오렌지 선물박스 + 개별 완충 포장</li></ul>
  <div class="gift-note" style="background:#fff6ef;border:1px solid #ffe0c4;border-radius:12px;padding:16px 18px;margin:16px 0;font-size:14px;line-height:1.9;color:#5a4a30">
  <b style="color:#c9640a">🎁 선물 포장 안내</b><br>
  · 하나하나 완충망으로 개별 포장하여 신선함 그대로 안전하게 배송합니다.<br>
  · <b>선물가방 또는 보자기 포장</b> 선택 시 <b>2,500원</b>이 추가됩니다.<br>
  · 생물 특성상 개체별 크기·무게 및 포장 개수는 다소 달라질 수 있습니다.
</div>
<img src="images/giftset/orchard/detail-options.jpg" alt="상품 구성 및 가격 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
<img src="images/giftset/orchard/detail-packaging.jpg" alt="마음을 전하는 신선한 망고 선물 · 포장 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
</div>',
    'origin' => '수입산',
    'variety' => '애플망고',
    'grade' => '선물용',
    'box_spec' => '2kg 내외 · 3~6과',
    'weight_kg' => '2.00',
    'moq' => 1,
    'stock' => 100,
    'tax_type' => 'exempt',
    'price' => 37000,
    'wholesale_price' => NULL,
    'member_price' => NULL,
    'thumbnail' => 'images/giftset/orchard/applebox-main.jpg',
    'images' => 
    array (
      0 => 'images/giftset/orchard/applemango-box6.jpg',
      1 => 'images/giftset/orchard/box-closed.jpg',
    ),
    'sale_status' => 'on_sale',
    'is_new' => true,
    'is_best' => false,
    'is_featured' => false,
    'is_active' => true,
  ),
  4 => 
  array (
    'code' => 'GSP004',
    'category_slug' => 'giftset',
    'name' => '골드망고 선물박스 (1.5kg 내외·3~6과)',
    'slug' => 'gold-mango-giftbox-15kg',
    'unit' => 'BOX',
    'maker' => 'Fresh Fresh',
    'summary' => '부드러운 골드망고 1.5kg 내외(3~6과) · 오렌지 선물박스',
    'description' => '<div class="prod-detail">
  <p style="font-size:15px;line-height:1.85;color:#3d2a12">부드럽고 달콤한 <b>골드망고</b>를 실속 있게 담은 선물박스입니다. 1.5kg 내외(3~6과)로 간편하게 마음을 전할 수 있습니다.</p>
  <ul style="font-size:14px;line-height:1.95;color:#5a4a30;margin:14px 0 6px;padding-left:18px"><li>구성 : 골드망고 <b>1.5kg 내외 (3~6과)</b></li><li>가격 : 36,400원</li><li>포장 : 오렌지 선물박스 + 개별 완충 포장</li></ul>
  <div class="gift-note" style="background:#fff6ef;border:1px solid #ffe0c4;border-radius:12px;padding:16px 18px;margin:16px 0;font-size:14px;line-height:1.9;color:#5a4a30">
  <b style="color:#c9640a">🎁 선물 포장 안내</b><br>
  · 하나하나 완충망으로 개별 포장하여 신선함 그대로 안전하게 배송합니다.<br>
  · <b>선물가방 또는 보자기 포장</b> 선택 시 <b>2,500원</b>이 추가됩니다.<br>
  · 생물 특성상 개체별 크기·무게 및 포장 개수는 다소 달라질 수 있습니다.
</div>
<img src="images/giftset/orchard/detail-options.jpg" alt="상품 구성 및 가격 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
<img src="images/giftset/orchard/detail-packaging.jpg" alt="마음을 전하는 신선한 망고 선물 · 포장 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
</div>',
    'origin' => '수입산',
    'variety' => '골드망고',
    'grade' => '선물용',
    'box_spec' => '1.5kg 내외 · 3~6과',
    'weight_kg' => '1.50',
    'moq' => 1,
    'stock' => 100,
    'tax_type' => 'exempt',
    'price' => 36400,
    'wholesale_price' => NULL,
    'member_price' => NULL,
    'thumbnail' => 'images/giftset/orchard/goldbox-main.jpg',
    'images' => 
    array (
      0 => 'images/giftset/orchard/goldbox-6.jpg',
    ),
    'sale_status' => 'on_sale',
    'is_new' => true,
    'is_best' => false,
    'is_featured' => false,
    'is_active' => true,
  ),
  5 => 
  array (
    'code' => 'GSP005',
    'category_slug' => 'giftset',
    'name' => '프리미엄 혼합과일 선물세트 (애플망고·아보카도·오렌지·키위)',
    'slug' => 'premium-mixed-fruit-giftset',
    'unit' => 'BOX',
    'maker' => 'Fresh Fresh',
    'summary' => '애플망고·아보카도·오렌지·키위를 한 상자에 · 프리미엄 혼합과일 선물세트',
    'description' => '<div class="prod-detail">
  <p style="font-size:15px;line-height:1.85;color:#3d2a12"><b>애플망고·아보카도·오렌지·키위</b>를 한 상자에 담은 프리미엄 혼합과일 선물세트입니다. 다양한 과일을 고루 즐길 수 있어 폭넓게 사랑받는 인기 구성입니다.</p>
  <ul style="font-size:14px;line-height:1.95;color:#5a4a30;margin:14px 0 6px;padding-left:18px"><li>구성 : 애플망고 · 아보카도 · 오렌지 · 키위</li><li>가격 : 34,500원</li><li>포장 : 선물박스 + 개별 완충 포장</li></ul>
  <div class="gift-note" style="background:#fff6ef;border:1px solid #ffe0c4;border-radius:12px;padding:16px 18px;margin:16px 0;font-size:14px;line-height:1.9;color:#5a4a30">
  <b style="color:#c9640a">🎁 선물 포장 안내</b><br>
  · 하나하나 완충망으로 개별 포장하여 신선함 그대로 안전하게 배송합니다.<br>
  · <b>선물가방 또는 보자기 포장</b> 선택 시 <b>2,500원</b>이 추가됩니다.<br>
  · 생물 특성상 개체별 크기·무게 및 포장 개수는 다소 달라질 수 있습니다.
</div>
<img src="images/giftset/orchard/detail-options.jpg" alt="상품 구성 및 가격 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
<img src="images/giftset/orchard/detail-packaging.jpg" alt="마음을 전하는 신선한 망고 선물 · 포장 안내" style="width:100%;height:auto;display:block;border-radius:10px;margin:10px 0">
</div>',
    'origin' => '수입산',
    'variety' => '혼합과일',
    'grade' => '프리미엄',
    'box_spec' => '애플망고·아보카도·오렌지·키위 구성',
    'weight_kg' => NULL,
    'moq' => 1,
    'stock' => 100,
    'tax_type' => 'exempt',
    'price' => 34500,
    'wholesale_price' => NULL,
    'member_price' => NULL,
    'thumbnail' => 'images/giftset/orchard/mixed-main.jpg',
    'images' => 
    array (
      0 => 'images/giftset/orchard/mixed-real.jpg',
    ),
    'sale_status' => 'on_sale',
    'is_new' => true,
    'is_best' => false,
    'is_featured' => false,
    'is_active' => true,
  ),
];

        foreach ($rows as $row) {
            $categoryId = Category::where('slug', $row['category_slug'])->value('id');
            if (! $categoryId) {
                $this->command?->warn("카테고리 미존재(slug={$row['category_slug']}) → {$row['code']} 건너뜀");
                continue;
            }
            $code = $row['code'];
            unset($row['code'], $row['category_slug']);
            $row['category_id'] = $categoryId;
            $row['seller_id'] = null;

            Product::updateOrCreate(['code' => $code], $row);
            $this->command?->info("등록/갱신: {$code} · {$row['name']}");
        }
    }
}
