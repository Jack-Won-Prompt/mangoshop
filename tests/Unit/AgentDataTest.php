<?php

namespace Tests\Unit;

use App\Services\AgentData;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * 자료 바꾸기가 '하면 안 되는 일' 을 실제로 막는지 본다.
 *
 * 운영 자료를 사람 확인 없이 바꾸는 쪽이라, 막는 규칙이 느슨해지면
 * 바로 사고가 된다. check() 를 직접 불러 규칙만 따로 확인한다.
 */
class AgentDataTest extends TestCase
{
    private function check(array $op): array
    {
        $r = new ReflectionClass(AgentData::class);
        return $r->getMethod('check')->invoke(new AgentData, null, $op);
    }

    public function test_회원_표는_손대지_못한다(): void
    {
        $r = $this->check(['table' => 'users', 'match' => ['id' => 1], 'set' => ['name' => '아무개']]);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('손댈 수 있는 자리가 아닙니다', $r['reason']);
    }

    public function test_주문_결제_정산_표도_손대지_못한다(): void
    {
        foreach (['orders', 'order_items', 'seller_settlements', 'credit_accounts', 'coupons'] as $t) {
            $r = $this->check(['table' => $t, 'match' => ['id' => 1], 'set' => ['x' => 1]]);
            $this->assertFalse($r['ok'], "{$t} 가 열려 있습니다");
        }
    }

    public function test_허용된_표라도_돈_칸은_막는다(): void
    {
        foreach (['price', 'member_price', 'discount_rate', 'commission'] as $col) {
            $r = $this->check(['table' => 'categories', 'match' => ['slug' => 'x'], 'set' => [$col => 1000]]);
            $this->assertFalse($r['ok'], "{$col} 이 열려 있습니다");
        }
    }

    public function test_비밀번호_열쇠_칸도_막는다(): void
    {
        foreach (['password', 'api_token', 'secret_key', 'password_hash'] as $col) {
            $r = $this->check(['table' => 'categories', 'match' => ['slug' => 'x'], 'set' => [$col => 'a']]);
            $this->assertFalse($r['ok'], "{$col} 이 열려 있습니다");
        }
    }

    public function test_어디를_바꿀지_비어_있으면_하지_않는다(): void
    {
        $this->assertFalse($this->check(['table' => 'categories', 'match' => [], 'set' => ['icon' => 'x']])['ok']);
        $this->assertFalse($this->check(['table' => 'categories', 'match' => ['slug' => 'x'], 'set' => []])['ok']);
    }

    public function test_표_이름이_없으면_하지_않는다(): void
    {
        $this->assertFalse($this->check(['match' => ['slug' => 'x'], 'set' => ['icon' => 'y']])['ok']);
    }

    public function test_한_번에_처리할_일의_수에_상한이_있다(): void
    {
        $r = new ReflectionClass(AgentData::class);
        $this->assertSame(5, $r->getConstant('MAX_OPS'));
    }

    public function test_그림을_받는_칸만_첨부를_걸_수_있다(): void
    {
        $r = new ReflectionClass(AgentData::class);
        $this->assertSame(['image', 'logo', 'cover', 'thumbnail', 'photo'], $r->getConstant('IMAGE_COLUMNS'));
        // icon 은 글자 칸이라 첨부를 걸 수 없어야 한다
        $this->assertNotContains('icon', $r->getConstant('IMAGE_COLUMNS'));
    }

    public function test_손댈_수_있는_표에_거래_회원_자료가_없다(): void
    {
        $allow = (new ReflectionClass(AgentData::class))->getConstant('ALLOW_TABLES');
        foreach (['users', 'orders', 'order_items', 'payments', 'carts', 'cart_items',
            'sellers', 'seller_settlements', 'products', 'product_options'] as $t) {
            $this->assertNotContains($t, $allow, "{$t} 가 허용 목록에 있습니다");
        }
    }
}
