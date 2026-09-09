<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeAnswer;
use App\Models\RecipeCategory;
use App\Models\RecipeComment;
use App\Models\RecipeQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 레시피 커뮤니티 데모 콘텐츠(사람이 등록한 것처럼) — 카테고리별 30~70개.
 * 관리자 공식 레시피(사진) + 회원 레시피(사진/유튜브) + 질문/답변 + 댓글 + 좋아요/조회.
 * 실행: php artisan db:seed --class=RecipeDemoSeeder
 */
class RecipeDemoSeeder extends Seeder
{
    /** 카테고리별 요리/이미지/질문 소재 */
    private array $data = [
        '망고' => [
            'dishes' => ['망고 스무디', '망고 빙수', '망고 치즈케이크', '망고 요거트볼', '망고 라씨', '애플망고 푸딩', '망고 판나코타', '망고 소르베', '망고 찹쌀떡', '망고 에이드', '망고 무스', '망고 탕후루', '망고 살사', '망고 크레페', '망고 스티키라이스', '망고 슬러시', '망고 젤리', '망고 티라미수'],
            'imgs' => ['mango-fruit-0.jpg', 'mango-fruit-1.jpg', 'mango-fruit-2.jpg', 'mango-fruit-3.jpg', 'mango-nam-dok-mai-0.jpg', 'mango-nam-dok-mai-1.jpg', 'green-mango-0.jpg'],
            'q' => ['애플망고 어떻게 보관해야 오래 가나요?', '안 익은 망고 빨리 익히는 법 있을까요?', '망고 껍질 예쁘게 깎는 법 알려주세요', '망고 스무디에 우유 대신 뭘 넣으면 좋을까요?', '망고 고를 때 잘 익은 거 구별법이요', '냉동망고로도 빙수 되나요?'],
        ],
        '아보카도' => [
            'dishes' => ['아보카도 토스트', '과카몰리', '아보카도 샐러드', '아보카도 비빔밥', '아보카도 샌드위치', '아보카도 스무디', '아보카도 계란볼', '아보카도 명란덮밥', '아보카도 김밥', '아보카도 참치포케', '아보카도 오믈렛', '아보카도 크림파스타'],
            'imgs' => ['avocado-fruit-0.jpg', 'tropical-fruit-market-0.jpg'],
            'q' => ['아보카도 후숙 어떻게 하나요?', '아보카도 갈변 막는 법 있을까요?', '잘 익은 아보카도 고르는 법이요', '아보카도 씨 쉽게 빼는 방법?', '딱딱한 아보카도 활용법 있나요?'],
        ],
        '열대과일' => [
            'dishes' => ['두리안 아이스크림', '리치 에이드', '망고스틴 화채', '용과 스무디볼', '트로피컬 과일화채', '리치 젤리', '패션후르츠 소스', '용과 요거트', '두리안 빵', '열대과일 탕후루', '망고스틴 셔벗', '용과 스무디'],
            'imgs' => ['durian-fruit-0.jpg', 'lychee-fruit-0.jpg', 'mangosteen-0.jpg', 'pitaya-0.jpg', 'tropical-fruit-market-0.jpg'],
            'q' => ['두리안 냄새 줄이는 보관법 있나요?', '용과 잘 익은 거 어떻게 고르죠?', '리치 껍질 쉽게 까는 법이요', '망고스틴 색이 진하면 상한 건가요?', '용과 흰색이랑 빨간색 차이가 뭔가요?'],
        ],
        '시트러스' => [
            'dishes' => ['오렌지 청', '자몽 에이드', '오렌지 마멀레이드', '자몽 요거트', '오렌지 파운드케이크', '자몽 셔벗', '오렌지 드레싱', '자몽 하이볼', '오렌지 젤리', '레몬 오렌지 청', '자몽 샐러드', '오렌지 케이크'],
            'imgs' => ['orange-fruit-0.jpg', 'grapefruit-0.jpg'],
            'q' => ['오렌지 청 설탕 비율 어떻게 하나요?', '자몽 쓴맛 줄이는 법 있을까요?', '오렌지 속껍질 깔끔하게 벗기는 법?', '자몽청 오래 보관하려면요?', '오렌지 껍질 왁스 제거 어떻게 하죠?'],
        ],
        '파인애플' => [
            'dishes' => ['파인애플 볶음밥', '파인애플 에이드', '파인애플 탕수육', '파인애플 피자', '파인애플 스무디', '파인애플 잼', '파인애플 샐러드', '파인애플 소르베', '파인애플 화채', '파인애플 카레', '파인애플 새우볶음', '파인애플 요거트'],
            'imgs' => ['pineapple-fruit-0.jpg', 'tropical-fruit-market-0.jpg'],
            'q' => ['파인애플 혀 아린 거 없애는 법 있나요?', '파인애플 심 활용법 있을까요?', '잘 익은 파인애플 고르는 법이요', '파인애플 손질 쉽게 하는 법?', '파인애플 냉동 보관해도 되나요?'],
        ],
        '디저트음료' => [
            'dishes' => ['과일 화채', '트로피컬 에이드', '과일 요거트볼', '수제 과일청', '과일 산도', '생과일 빙수', '과일 탕후루', '과일 스무디', '과일 파르페', '과일 젤리', '과일 판나코타', '과일 무스컵'],
            'imgs' => ['mango-fruit-2.jpg', 'tropical-fruit-market-0.jpg', 'orange-fruit-0.jpg', 'pineapple-fruit-0.jpg', 'pitaya-0.jpg', 'mango-fruit-1.jpg'],
            'q' => ['과일청 곰팡이 안 피게 하는 법?', '빙수 얼음 집에서 곱게 가는 법 있나요?', '과일 스무디 안 갈변되게 하려면요?', '수제청 설탕 대신 올리고당 써도 되나요?', '과일 화채 사이다 말고 뭐 넣죠?'],
        ],
    ];

    private array $titlePrefix = ['', '집에서 만드는 ', '초간단 ', '아이도 잘 먹는 ', '실패없는 ', '카페st ', '10분 완성 ', '남는 과일로 ', '다이어트 ', '여름 별미 '];
    private array $titleSuffix = ['', ' 만들기', ' 레시피', ' 만드는 법', ' 황금레시피', ' (feat.꿀팁)'];

    private array $nicknames = ['요리하는망고', '과일덕후', '초보주부일상', '달콤한하루', '냠냠키친', '제주감귤', '트로피컬맘', '홈카페러버', '오늘뭐먹지', '주말요리사', '망고사랑', '건강한식탁', '레시피수집가', '두아이맘', '살림9단', '과일가게딸', '비건요리', '자취생요리', '베이킹덕후', '싱싱마켓', '열대과일홀릭', '디저트공방', '요리왕비룡', '엄마손맛'];

    public function run(): void
    {
        $authors = $this->ensureAuthors();
        $admin = User::where('is_admin', true)->first();

        foreach ($this->data as $slug => $set) {
            $cat = RecipeCategory::where('slug', $slug)->first();
            if (! $cat) {
                continue;
            }
            $n = random_int(30, 70);
            for ($i = 0; $i < $n; $i++) {
                $roll = random_int(1, 100);
                if ($roll <= 12) {
                    $this->makeOfficialRecipe($cat, $set, $admin);
                } elseif ($roll <= 62) {
                    $this->makeUserRecipe($cat, $set, $authors);
                } else {
                    $this->makeQuestion($cat, $set, $authors, $admin);
                }
            }
            $this->command?->info("[$slug] {$n}개 생성");
        }
    }

    private function ensureAuthors(): array
    {
        $ids = [];
        foreach ($this->nicknames as $i => $nick) {
            $u = User::firstOrCreate(
                ['email' => 'member'.($i + 1).'@mango.demo'],
                ['name' => $nick, 'password' => Hash::make(Str::random(16)), 'member_type' => 'general', 'is_admin' => false, 'point' => 0]
            );
            $ids[] = $u->id;
        }

        return $ids;
    }

    private function randTime(): Carbon
    {
        return now()->subDays(random_int(0, 75))->subMinutes(random_int(0, 1440));
    }

    private function dish(array $set): string
    {
        return $set['dishes'][array_rand($set['dishes'])];
    }

    private function img(array $set): string
    {
        return 'images/fruit/'.$set['imgs'][array_rand($set['imgs'])];
    }

    private function body(string $dish): string
    {
        $intros = [
            "집에 남은 과일로 만들어본 {$dish}예요. 생각보다 훨씬 간단하더라고요!",
            "요즘 자주 해먹는 {$dish} 레시피 공유해요. 아이들이 정말 좋아합니다 :)",
            "카페에서 사먹던 {$dish}, 집에서 만들어도 맛이 똑같아요.",
            "실패 없이 만드는 {$dish} 황금비율 찾았어요. 따라해보세요!",
            "더운 날 시원하게 먹기 좋은 {$dish}입니다.",
        ];
        $steps = [
            "1) 과일을 깨끗이 씻어 적당한 크기로 썰어주세요.\n2) 준비한 재료와 함께 믹서에 넣고 곱게 갈아줍니다.\n3) 기호에 따라 꿀이나 시럽으로 단맛을 맞춰주세요.\n4) 그릇에 예쁘게 담으면 완성!",
            "1) 재료를 계량해 준비합니다.\n2) 과일은 냉동해두면 더 시원하고 꾸덕해요.\n3) 중불에서 저어가며 조려주세요.\n4) 한 김 식힌 뒤 냉장 보관하면 됩니다.",
            "1) 과일 손질 후 설탕에 30분 절여주세요.\n2) 나온 과즙과 함께 살짝 끓입니다.\n3) 식혀서 용기에 담아 완성합니다.",
        ];
        $tips = [
            "\n\n💡 꿀팁: 과일은 완전히 익은 걸 쓰면 설탕을 줄여도 충분히 달아요.",
            "\n\n💡 꿀팁: 차갑게 해서 먹으면 두 배로 맛있습니다.",
            "\n\n💡 꿀팁: 남으면 소분해서 냉동 보관하세요.",
            '',
        ];

        return str_replace('{dish}', $dish, $intros[array_rand($intros)])."\n\n"
            .$steps[array_rand($steps)].$tips[array_rand($tips)];
    }

    private function title(string $dish): string
    {
        return trim($this->titlePrefix[array_rand($this->titlePrefix)].$dish.$this->titleSuffix[array_rand($this->titleSuffix)]);
    }

    private function makeOfficialRecipe(RecipeCategory $cat, array $set, ?User $admin): void
    {
        $dish = $this->dish($set);
        $t = $this->randTime();
        $r = Recipe::create([
            'recipe_category_id' => $cat->id, 'user_id' => null,
            'title' => '[망고샵 공식] '.$this->title($dish), 'slug' => Recipe::uniqueSlug($dish.'-off-'.Str::random(4), $cat->id),
            'summary' => "{$cat->name}으로 만드는 {$dish}, 망고샵이 알려드려요.",
            'body' => '<p>'.nl2br(e($this->body($dish))).'</p>',
            'cover_image' => $this->img($set), 'is_official' => true, 'is_pinned' => random_int(1, 100) <= 30,
            'status' => 'published', 'view_count' => random_int(120, 900), 'like_count' => random_int(15, 120),
            'tags' => Recipe::normalizeTags($cat->name.','.explode(' ', $dish)[0].',레시피'),
            'published_at' => $t, 'created_at' => $t, 'updated_at' => $t,
        ]);
        $this->addPhotos($r, $set, random_int(1, 3));
        $this->addComments($r, random_int(2, 9));
    }

    private function makeUserRecipe(RecipeCategory $cat, array $set, array $authors): void
    {
        $dish = $this->dish($set);
        $t = $this->randTime();
        $r = Recipe::create([
            'recipe_category_id' => $cat->id, 'user_id' => $authors[array_rand($authors)],
            'title' => $this->title($dish), 'slug' => Recipe::uniqueSlug($dish.'-'.Str::random(4), $cat->id),
            'summary' => random_int(1, 2) === 1 ? "{$dish} 만들어봤어요!" : null,
            'body' => '<p>'.nl2br(e($this->body($dish))).'</p>',
            'cover_image' => $this->img($set), 'is_official' => false, 'is_pinned' => false,
            'status' => 'published', 'view_count' => random_int(10, 400), 'like_count' => random_int(0, 60),
            'tags' => random_int(1, 100) <= 70 ? Recipe::normalizeTags($cat->name.','.explode(' ', $dish)[0]) : null,
            'published_at' => $t, 'created_at' => $t, 'updated_at' => $t,
        ]);
        $this->addPhotos($r, $set, random_int(1, 3));
        $this->addComments($r, random_int(0, 7));
    }

    private function makeQuestion(RecipeCategory $cat, array $set, array $authors, ?User $admin): void
    {
        $t = $this->randTime();
        $q = RecipeQuestion::create([
            'recipe_category_id' => $cat->id, 'user_id' => $authors[array_rand($authors)],
            'title' => $set['q'][array_rand($set['q'])],
            'body' => '궁금해서 여쭤봐요. 다들 어떻게 하시는지 알려주시면 감사하겠습니다!',
            'status' => 'published', 'view_count' => random_int(20, 300),
            'created_at' => $t, 'updated_at' => $t,
        ]);
        $ansTexts = [
            '저는 냉장 보관하고 3~4일 안에 먹어요. 그 이상은 냉동 추천드려요!',
            '실온에 두고 후숙한 뒤 냉장고에 넣으면 오래가요.',
            '저도 처음엔 헤맸는데 신문지에 싸서 두니까 훨씬 낫더라고요.',
            '키친타월로 감싸서 밀폐용기에 넣어보세요. 확실히 오래갑니다.',
            '덜 익었으면 사과랑 같이 봉지에 넣어두면 빨리 익어요!',
            '저는 그냥 손질해서 소분 냉동해요. 스무디 할 때 딱 좋아요.',
        ];
        $cnt = random_int(1, 4);
        for ($k = 0; $k < $cnt; $k++) {
            $isAdmin = ($k === 0 && random_int(1, 100) <= 35);
            $at = (clone $t)->addHours(random_int(1, 60));
            RecipeAnswer::create([
                'recipe_question_id' => $q->id,
                'user_id' => $isAdmin ? ($admin?->id) : $authors[array_rand($authors)],
                'body' => $isAdmin ? ('망고샵입니다 :) '.$ansTexts[array_rand($ansTexts)]) : $ansTexts[array_rand($ansTexts)],
                'is_admin' => $isAdmin, 'status' => 'published',
                'created_at' => $at, 'updated_at' => $at,
            ]);
        }
        $q->update(['answer_count' => $cnt]);
        if (random_int(1, 100) <= 30) {
            $this->addQuestionPhoto($q, $set);
        }
    }

    private function addPhotos(Recipe $r, array $set, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $r->images()->create(['path' => $this->img($set), 'sort' => $i, 'created_at' => $r->created_at, 'updated_at' => $r->created_at]);
        }
    }

    private function addQuestionPhoto(RecipeQuestion $q, array $set): void
    {
        $q->images()->create(['path' => $this->img($set), 'sort' => 0, 'created_at' => $q->created_at, 'updated_at' => $q->created_at]);
    }

    private function addComments(Recipe $r, int $count): void
    {
        if ($count <= 0) {
            return;
        }
        $texts = ['와 맛있겠어요! 저도 해봐야겠다', '레시피 감사합니다 :)', '이거 어제 만들었는데 성공했어요!', '아이들이 너무 좋아하네요', '사진만 봐도 군침 도네요 ㅎㅎ', '재료가 간단해서 좋아요', '북마크 해둡니다!', '망고샵 과일로 하니까 더 맛있는듯', '설탕 좀 줄여도 될까요?', '완전 꿀팁이네요 감사해요'];
        $authors = User::where('is_admin', false)->where('email', 'like', '%@mango.demo')->pluck('id')->all();
        if (empty($authors)) {
            return;
        }
        $made = 0;
        for ($i = 0; $i < $count; $i++) {
            $ct = (clone $r->created_at)->addHours(random_int(1, 200));
            RecipeComment::create([
                'recipe_id' => $r->id, 'user_id' => $authors[array_rand($authors)],
                'body' => $texts[array_rand($texts)], 'is_admin' => false, 'status' => 'published',
                'created_at' => $ct, 'updated_at' => $ct,
            ]);
            $made++;
        }
        $r->update(['comment_count' => $made]);
    }
}
