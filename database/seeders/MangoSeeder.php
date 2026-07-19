<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\Faq;
use App\Models\Notice;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 망고샵 데모 시드 — 멀티벤더 수입 과일 오픈마켓.
 * 실행: php artisan migrate:fresh --seed
 */
class MangoSeeder extends Seeder
{
    public function run(): void
    {
        $this->settings();
        $this->users();
        $cats = $this->categories();
        $sellers = $this->sellers();
        $this->products($cats, $sellers);
        $this->banners();
        $this->community();
    }

    private function settings(): void
    {
        // config/site.php 기본값을 그대로 DB에 심어 관리자 화면에서 편집 가능하게
        Setting::put('site', config('site'));
    }

    private function users(): void
    {
        User::updateOrCreate(['email' => 'admin@mangoshop.co.kr'], [
            'name' => '플랫폼관리자', 'password' => Hash::make('mango!2026'),
            'is_admin' => true, 'member_type' => 'retail',
        ]);

        // 승인된 도매 회원 — 도매가/여신 적용 대상
        $wholesale = User::updateOrCreate(['email' => 'buyer@test.com'], [
            'name' => '김도매', 'password' => Hash::make('test1234'),
            'member_type' => 'wholesale', 'biz_status' => 'approved', 'grade' => 'gold',
            'company_name' => '싱싱청과', 'biz_no' => '211-86-12345', 'biz_type' => '과일도매',
            'phone' => '010-2222-3333', 'point' => 3000,
            'postcode' => '05000', 'address1' => '서울 송파구 오금로 100', 'address2' => '201호',
        ]);
        // 도매 회원 여신 한도 500만원
        CreditAccount::updateOrCreate(['user_id' => $wholesale->id], [
            'limit_amount' => 5000000, 'used_amount' => 0, 'terms' => 'net30', 'status' => 'active',
        ]);
        $wholesale->addresses()->firstOrCreate(
            ['receiver_name' => '김도매', 'address1' => '서울 송파구 오금로 100'],
            ['label' => '본점', 'receiver_phone' => '010-2222-3333', 'postcode' => '05000',
             'address2' => '201호', 'is_default' => true],
        );

        // 소매 회원
        User::updateOrCreate(['email' => 'user@test.com'], [
            'name' => '홍길동', 'password' => Hash::make('test1234'),
            'member_type' => 'retail', 'phone' => '010-1111-2222', 'point' => 3000,
        ]);

        // 승인 대기 도매 회원
        User::updateOrCreate(['email' => 'pending@test.com'], [
            'name' => '박대기', 'password' => Hash::make('test1234'),
            'member_type' => 'wholesale', 'biz_status' => 'pending',
            'company_name' => '대기청과', 'biz_no' => '123-45-67890', 'phone' => '010-9999-8888',
        ]);

        // 구매 대행자 — 여러 구매자(소매처)를 대신해 주문, 주문금액의 2% 캐시백
        $agent = User::updateOrCreate(['email' => 'agent@test.com'], [
            'name' => '이대행', 'password' => Hash::make('test1234'),
            'member_type' => 'wholesale', 'biz_status' => 'approved', 'grade' => 'gold',
            'company_name' => '대행상사', 'biz_no' => '507-81-55555', 'phone' => '010-7777-6666',
            'is_agent' => true, 'cashback_rate' => 2.00,
        ]);
        foreach ([
            ['현대청과', '111-22-33333', '010-1000-0001'],
            ['우리마트', '222-33-44444', '010-1000-0002'],
            ['신선상회', '333-44-55555', '010-1000-0003'],
        ] as [$bn, $biz, $ph]) {
            $agent->buyers()->firstOrCreate(['name' => $bn], ['biz_no' => $biz, 'phone' => $ph]);
        }
    }

    /** 카테고리: 과일 대분류 + 망고 품종 중분류 */
    private function categories(): array
    {
        $tree = [
            ['망고', 'mango', '🥭', [
                ['애플망고', 'apple-mango'], ['옐로우망고', 'yellow-mango'],
                ['그린망고', 'green-mango'], ['망고스틴', 'mangosteen'],
            ]],
            ['아보카도', 'avocado', '🥑', [['하스 아보카도', 'hass-avocado']]],
            ['열대과일', 'tropical', '🍍', [
                ['두리안', 'durian'], ['용과', 'dragonfruit'], ['리치', 'lychee'], ['파인애플', 'pineapple'],
            ]],
            ['시트러스', 'citrus', '🍊', [['오렌지', 'orange'], ['자몽', 'grapefruit']]],
            ['베리류', 'berry', '🫐', [['블루베리', 'blueberry']]],
            ['선물세트', 'giftset', '🎁', []],
        ];

        $map = [];
        foreach ($tree as $i => [$name, $slug, $icon, $children]) {
            $root = Category::updateOrCreate(['slug' => $slug],
                ['name' => $name, 'icon' => $icon, 'sort_order' => $i, 'is_active' => true]);
            $map[$slug] = $root;
            foreach ($children as $j => [$cn, $cs]) {
                $map[$cs] = Category::updateOrCreate(['slug' => $cs],
                    ['name' => $cn, 'parent_id' => $root->id, 'sort_order' => $j, 'is_active' => true]);
            }
        }

        return $map;
    }

    /** 입점 수입사 4곳 (+ 판매자 콘솔 로그인 계정) */
    private function sellers(): array
    {
        $defs = [
            ['트로피컬수입', 'tropical-import', '태국', '태국 현지 농장 직계약 애플망고 전문 수입사', '방콘무역'],
            ['비엣프레시', 'viet-fresh', '베트남', '베트남 옐로우망고·용과 산지직송', '호치민상사'],
            ['필리핀골드', 'ph-gold', '필리핀', '필리핀 세부 애플망고 항공직송', '세부트레이딩'],
            ['제스트팜', 'zest-farm', '페루', '페루·칠레 아보카도와 시트러스 수입', '안데스무역'],
        ];
        $sellers = [];
        foreach ($defs as $i => [$name, $slug, $origin, $intro, $ceo]) {
            $acct = User::updateOrCreate(['email' => $slug.'@seller.com'], [
                'name' => $name.' 담당자', 'password' => Hash::make('test1234'),
                'member_type' => 'wholesale', 'biz_status' => 'approved',
                'company_name' => $name,
            ]);
            $sellers[$slug] = Seller::updateOrCreate(['slug' => $slug], [
                'user_id' => $acct->id, 'name' => $name, 'origin_focus' => $origin,
                'ceo_name' => $ceo, 'intro' => $intro, 'status' => 'approved', 'is_active' => true,
                'biz_no' => sprintf('%03d-86-%05d', 100 + $i, 10000 + $i),
                'phone' => '02-500-'.sprintf('%04d', 1000 + $i),
                'email' => $slug.'@seller.com',
                'commission_rate' => [8, 10, 10, 12][$i],
                'shipping_fee' => 3000, 'free_shipping_threshold' => 50000,
                'coldchain' => true, 'shipping_notice' => '제주·도서산간 추가배송비 발생. 콜드체인 냉장배송.',
                'rating_sum' => [46, 38, 42, 30][$i], 'rating_count' => [10, 8, 9, 7][$i],
                'sort_order' => $i,
            ]);
        }

        return $sellers;
    }

    /** 상품 — 수입사별 망고/열대과일 */
    private function products(array $cats, array $sellers): void
    {
        // [name, seller_slug, category_slug, origin, variety, grade, box_spec, weight, price(소매), wholesale, moq, tiers, flags, image]
        $rows = [
            ['태국 남독마이 애플망고 5kg', 'tropical-import', 'apple-mango', '태국', '남독마이', '특', '5kg/9~12과', 5, 59000, 46000, 2,
                [['min_qty' => 5, 'price' => 44000], ['min_qty' => 10, 'price' => 42000]], ['best' => true, 'featured' => true], 'mango-nam-dok-mai-0.jpg'],
            ['태국 애플망고 프리미엄 3kg', 'tropical-import', 'apple-mango', '태국', '남독마이', '상', '3kg/6과', 3, 39000, 31000, 2,
                [['min_qty' => 10, 'price' => 29000]], ['new' => true], 'mango-nam-dok-mai-1.jpg'],
            ['태국 그린망고 5kg (조리용)', 'tropical-import', 'green-mango', '태국', '키유사워이', '상', '5kg', 5, 32000, 24000, 3, [], ['best' => true], 'mango-fruit-0.jpg'],
            ['망고스틴 3kg', 'tropical-import', 'mangosteen', '태국', '-', '특', '3kg', 3, 45000, 36000, 2, [], ['best' => true], 'mangosteen-0.jpg'],
            ['베트남 옐로우망고 6kg', 'viet-fresh', 'yellow-mango', '베트남', '캇추', '특', '6kg/12~15과', 6, 42000, 33000, 2,
                [['min_qty' => 5, 'price' => 31000], ['min_qty' => 10, 'price' => 29000]], ['best' => true, 'featured' => true], 'mango-fruit-2.jpg'],
            ['베트남 옐로우망고 실속 10kg', 'viet-fresh', 'yellow-mango', '베트남', '캇추', '중', '10kg', 10, 59000, 47000, 2, [], ['featured' => true], 'mango-fruit-3.jpg'],
            ['베트남 레드용과 5kg', 'viet-fresh', 'dragonfruit', '베트남', '레드', '상', '5kg', 5, 38000, 30000, 2, [], ['new' => true, 'best' => true], 'tropical-fruit-market-0.jpg'],
            ['베트남 리치 3kg', 'viet-fresh', 'lychee', '베트남', '-', '상', '3kg', 3, 29000, 22000, 3, [], ['best' => true], 'lychee-fruit-0.jpg'],
            ['필리핀 세부 애플망고 5kg', 'ph-gold', 'apple-mango', '필리핀', '카라바오', '특', '5kg/10~13과', 5, 55000, 43000, 2,
                [['min_qty' => 5, 'price' => 41000], ['min_qty' => 10, 'price' => 39000]], ['best' => true, 'featured' => true], 'mango-nam-dok-mai-0.jpg'],
            ['필리핀 애플망고 항공직송 2kg', 'ph-gold', 'apple-mango', '필리핀', '카라바오', '특', '2kg/4~5과', 2, 29000, 23000, 1, [], ['new' => true], 'mango-nam-dok-mai-0.jpg'],
            ['필리핀 골드 파인애플 6입', 'ph-gold', 'pineapple', '필리핀', 'MD2', '상', '6입', 9, 27000, 21000, 2, [], ['best' => true], 'pineapple-fruit-0.jpg'],
            ['페루 하스 아보카도 4kg', 'zest-farm', 'hass-avocado', '페루', '하스', '상', '4kg/18~22입', 4, 32000, 24000, 2,
                [['min_qty' => 10, 'price' => 22000]], ['best' => true, 'featured' => true], 'avocado-fruit-0.jpg'],
            ['칠레 네이블 오렌지 10kg', 'zest-farm', 'orange', '칠레', '네이블', '상', '10kg', 10, 39000, 30000, 2, [], ['new' => true], 'orange-fruit-0.jpg'],
            ['남아공 자몽 8kg', 'zest-farm', 'grapefruit', '남아공', '스타루비', '상', '8kg', 8, 34000, 26000, 2, [], ['new' => true, 'best' => true], 'grapefruit-0.jpg'],
        ];

        foreach ($rows as $i => $r) {
            [$name, $ss, $cs, $origin, $variety, $grade, $box, $weight, $price, $wholesale, $moq, $tiers, $flags, $img] = $r;
            $seller = $sellers[$ss];
            $cat = $cats[$cs] ?? null;
            // 한글명은 Str::slug로 슬러그화되지 않으므로 수입사-품종-순번으로 고유 슬러그 생성
            $slug = $ss.'-'.$cs.'-'.($i + 1);
            Product::updateOrCreate(['slug' => $slug], [
                'seller_id'   => $seller->id,
                'category_id' => $cat?->id,
                'name'        => $name,
                'thumbnail'   => 'images/fruit/'.$img,
                'code'        => 'MG'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'unit'        => 'BOX',
                'origin'      => $origin,
                'variety'     => $variety,
                'grade'       => $grade,
                'box_spec'    => $box,
                'weight_kg'   => $weight,
                'storage_method' => '냉장 0~5℃ 보관, 후숙 후 섭취',
                'inbound_date'   => now()->subDays($i % 7),
                'expiry_date'    => now()->addDays(14),
                'lot_no'      => 'LOT'.now()->format('ym').str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'summary'     => "{$origin}산 {$variety} · {$grade}등급 · {$box}",
                'description' => "<p>{$origin} 현지 농장에서 엄선한 {$name}입니다. 콜드체인 냉장배송으로 신선하게 받아보세요.</p>",
                'price'       => $price,
                'wholesale_price' => $wholesale,
                'member_price'    => $wholesale,
                'moq'         => $moq,
                'price_tiers' => $tiers,
                'stock'       => 100 - $i * 3,
                'sale_status' => 'on_sale',
                'is_active'   => true,
                'is_best'     => $flags['best'] ?? false,
                'is_featured' => $flags['featured'] ?? false,
                'is_new'      => $flags['new'] ?? false,
                'sales_count' => (14 - $i) * 5,
                'sort_order'  => $i,
            ]);
        }

        // 입고예정 상품 1건 (재입고 알림 데모용)
        Product::updateOrCreate(['slug' => 'thai-durian-preorder'], [
            'seller_id' => $sellers['tropical-import']->id,
            'category_id' => $cats['durian']->id ?? null,
            'name' => '태국 몬통 두리안 (입고예정)', 'unit' => 'BOX', 'origin' => '태국',
            'variety' => '몬통', 'grade' => '특', 'box_spec' => '3kg',
            'thumbnail' => 'images/fruit/durian-fruit-0.jpg',
            'price' => 89000, 'wholesale_price' => 72000, 'moq' => 1, 'stock' => 0,
            'sale_status' => 'inbound', 'expected_inbound_date' => now()->addDays(10),
            'summary' => '태국산 몬통 두리안 · 예약주문', 'is_active' => true, 'sort_order' => 99,
        ]);
    }

    private function banners(): void
    {
        $main = [
            ['title' => '태국 애플망고 산지직송', 'subtitle' => '남독마이 특품 5kg 최대 10% 도매 특가', 'bg_color' => '#ffffff', 'image' => 'images/fruit/mango-nam-dok-mai-0.jpg'],
            ['title' => '검증된 수입사 과일 마켓', 'subtitle' => '엄선한 수입사 과일을 한 곳에서 만나보세요', 'bg_color' => '#ffffff', 'image' => 'images/fruit/orange-fruit-0.jpg'],
            ['title' => '콜드체인 신선배송', 'subtitle' => '수입 과일을 산지 신선 그대로 냉장 직배송', 'bg_color' => '#ffffff', 'image' => 'images/fruit/pineapple-fruit-0.jpg'],
        ];
        foreach ($main as $i => $b) {
            Banner::updateOrCreate(['position' => 'main', 'title' => $b['title']],
                $b + ['sort_order' => $i, 'link' => '/products']);
        }
        // 현재 목록에 없는 옛 메인 배너 정리(재시드만으로 운영에서도 동기화)
        Banner::where('position', 'main')->whereNotIn('title', array_column($main, 'title'))->delete();
    }

    private function community(): void
    {
        $notices = [
            ['콜드체인 배송 안내', '모든 수입 과일은 냉장 콜드체인으로 배송됩니다. 제주·도서산간은 추가 배송비가 발생합니다.', true],
            ['도매회원 사업자 승인 절차', '사업자등록증 업로드 후 영업일 1일 내 승인되며, 승인 시 도매가와 여신 이용이 가능합니다.', true],
            ['입고 일정 안내', '태국 애플망고는 매주 화·금 입고됩니다. 입고예정 상품은 재입고 알림을 신청하세요.', false],
        ];
        foreach ($notices as $i => [$t, $b, $pin]) {
            Notice::updateOrCreate(['title' => $t],
                ['body' => $b, 'is_pinned' => $pin, 'views' => rand(20, 300), 'published_at' => now()->subDays($i)]);
        }

        $faqs = [
            ['회원', '도매회원과 소매회원의 차이는?', '도매회원은 사업자 인증·승인 후 도매가와 여신(후불) 결제를 이용할 수 있습니다. 소매회원은 별도 승인 없이 소매가로 구매합니다.'],
            ['주문/결제', '여러 수입사 상품을 함께 주문할 수 있나요?', '장바구니에서 수입사별로 묶여 표시되며, 한 번의 결제로 수입사별 주문이 생성됩니다.'],
            ['견적', '대량 구매 견적은 어떻게 받나요?', '상품 상세 또는 견적함에서 수입사에 견적을 요청하면, 수입사가 단가를 회신하고 견적 기반으로 주문할 수 있습니다.'],
            ['배송', '신선식품 반품이 가능한가요?', '신선식품 특성상 단순변심 반품은 제한되며, 상품 하자 시 수령 후 24시간 내 사진과 함께 접수해 주세요.'],
        ];
        foreach ($faqs as $i => [$c, $q, $a]) {
            Faq::updateOrCreate(['question' => $q], ['category' => $c, 'answer' => $a, 'sort_order' => $i]);
        }
    }
}
