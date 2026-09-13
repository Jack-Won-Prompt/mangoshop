<?php

namespace Tests\Feature;

use App\Support\SupportWorksReporter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Throwable;

class SupportWorksReporterTest extends TestCase
{
    private const MASK = '[숨김]';

    protected function setUp(): void
    {
        parent::setUp();

        // 자리표시 값. 실제 주소·토큰은 .env 에만 있다.
        config([
            'services.supportworks.error_url'   => 'https://supportworks.invalid/errors',
            'services.supportworks.error_token' => 'placeholder',
        ]);

        Http::fake();
    }

    /** report() 가 보낸 payload 를 꺼낸다. */
    private function sentPayload(Throwable $e, ?Request $request = null): array
    {
        SupportWorksReporter::report($e, $request);

        $recorded = Http::recorded();
        $this->assertCount(1, $recorded, '보고가 한 번 나가야 한다');

        return $recorded->first()[0]->data();
    }

    private function maskedUrl(string $uri): string
    {
        return $this->sentPayload(new RuntimeException('x'), Request::create($uri))['url'];
    }

    // ── 보낼지 말지 ──────────────────────────────────────────

    public function test_설정이_없으면_조용히_건너뛴다(): void
    {
        config(['services.supportworks.error_url' => null, 'services.supportworks.error_token' => null]);

        SupportWorksReporter::report(new RuntimeException('x'));

        Http::assertNothingSent();
    }

    public function test_404는_보내지_않는다(): void
    {
        SupportWorksReporter::report(new NotFoundHttpException('nope'));

        Http::assertNothingSent();
    }

    public function test_토큰을_Bearer로_싣고_필요한_항목을_보낸다(): void
    {
        SupportWorksReporter::report(new RuntimeException('boom'), Request::create('/orders/123'));

        Http::assertSent(function ($req) {
            $data = $req->data();

            return $req->url() === 'https://supportworks.invalid/errors'
                && $req->hasHeader('Authorization', 'Bearer placeholder')
                && $data['level'] === 'error'
                && $data['exception'] === RuntimeException::class
                && $data['message'] === 'boom'
                && str_ends_with($data['url'], '/orders/123')
                && is_int($data['line']) && $data['file'] !== ''
                && str_contains($data['trace'], '{main}');
        });
    }

    // ── 사이트를 망가뜨리지 않는다 ─────────────────────────────

    public function test_예외가_나도_사이트는_계속_돈다(): void
    {
        Route::get('/__sw_boom', fn () => throw new RuntimeException('일부러 낸 예외'));
        Route::get('/__sw_ok', fn () => 'ok');

        $this->get('http://localhost/__sw_boom')->assertStatus(500);
        $this->get('http://localhost/__sw_ok')->assertOk()->assertSee('ok');

        Http::assertSent(fn ($req) => $req->data()['message'] === '일부러 낸 예외');
    }

    public function test_보고가_실패해도_예외가_새어_나가지_않는다(): void
    {
        Http::fake(fn () => throw new ConnectionException('SupportWorks 에 닿지 않음'));

        Route::get('/__sw_boom', fn () => throw new RuntimeException('일부러 낸 예외'));
        Route::get('/__sw_ok', fn () => 'ok');

        $this->get('http://localhost/__sw_boom')->assertStatus(500);
        $this->get('http://localhost/__sw_ok')->assertOk();

        // 직접 불러도 밖으로 아무것도 던지지 않는다.
        SupportWorksReporter::report(new RuntimeException('x'), Request::create('/x'));
        $this->addToAssertionCount(1);
    }

    // ── maskUrl: 가림 ────────────────────────────────────────

    public function test_쿼리_token은_값만_가린다(): void
    {
        $secret = Str::random(32);
        $url = $this->maskedUrl('/search?token='.$secret);

        $this->assertStringContainsString('token='.self::MASK, $url);
        $this->assertStringNotContainsString($secret, $url);
    }

    public function test_쿼리_api_key와_access_token을_가린다(): void
    {
        $a = Str::random(12);
        $b = Str::random(12);
        $url = $this->maskedUrl("/x?api_key={$a}&access-token={$b}");

        $this->assertStringContainsString('api_key='.self::MASK, $url);
        $this->assertStringContainsString('access-token='.self::MASK, $url);
        $this->assertStringNotContainsString($a, $url);
        $this->assertStringNotContainsString($b, $url);
    }

    public function test_쿼리_이름_전체가_목록과_같으면_가린다(): void
    {
        $a = Str::random(12);
        $url = $this->maskedUrl("/x?apiKey={$a}");

        $this->assertStringContainsString('apiKey='.self::MASK, $url);
        $this->assertStringNotContainsString($a, $url);
    }

    public function test_배열_파라미터는_재귀로_가린다(): void
    {
        $pw = Str::random(12);
        $url = $this->maskedUrl("/x?user[name]=kim&user[password]={$pw}");

        $this->assertStringContainsString('user[password]='.self::MASK, $url);
        $this->assertStringContainsString('user[name]=kim', $url);
        $this->assertStringNotContainsString($pw, $url);
    }

    public function test_경로에_박힌_토큰을_가린다(): void
    {
        $token = Str::random(64);
        $url = $this->maskedUrl('/reset-password/'.$token);

        $this->assertStringEndsWith('/reset-password/'.self::MASK, $url);
        $this->assertStringNotContainsString($token, $url);
    }

    // ── maskUrl: 안 가림 ─────────────────────────────────────

    public function test_비슷하지만_다른_이름은_남긴다(): void
    {
        $url = $this->maskedUrl('/x?keyword=shoes&author=kim&monkey=1&keynote=1');

        foreach (['keyword=shoes', 'author=kim', 'monkey=1', 'keynote=1'] as $kept) {
            $this->assertStringContainsString($kept, $url);
        }
        $this->assertStringNotContainsString(self::MASK, $url);
    }

    public function test_평범한_경로는_남긴다(): void
    {
        $this->assertStringEndsWith('/orders/123', $this->maskedUrl('/orders/123'));
    }

    // ── maskMessage ─────────────────────────────────────────

    public function test_실제_QueryException의_바인딩_값이_남지_않는다(): void
    {
        config(['database.connections.sw_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);

        $secret = Str::random(32);

        try {
            DB::connection('sw_test')->select('select * from users where token = ? and id = ?', [$secret, 123]);
            $this->fail('QueryException 이 나야 한다');
        } catch (QueryException $e) {
            // 원래 메시지에는 바인딩 값이 그대로 들어 있다 — 그래서 가림이 필요하다.
            $this->assertStringContainsString($secret, $e->getMessage());

            $message = $this->sentPayload($e)['message'];
        }

        $this->assertStringNotContainsString($secret, $message);
        $this->assertStringContainsString('token = '.self::MASK.' and id = 123', $message);
        $this->assertStringContainsString('select * from users', $message);
    }

    public function test_따옴표와_괄호를_떼고_가운데를_본다(): void
    {
        $a = Str::random(30);
        $b = Str::random(30);
        $message = $this->sentPayload(new RuntimeException("bad value '{$a}', (see {$b})"))['message'];

        $this->assertSame("bad value '".self::MASK."', (see ".self::MASK.')', $message);
    }

    public function test_클래스_이름과_파일_경로는_남는다(): void
    {
        $text = 'Class App\Http\Controllers\Admin\ProductOptionController not found in /var/www/mangoshop/app/Http/Controllers/Admin/ProductOptionController.php';

        $this->assertSame($text, $this->sentPayload(new RuntimeException($text))['message']);
    }

    // ── trace ───────────────────────────────────────────────

    private function takesLongString(string $value): never
    {
        throw new RuntimeException('trace 시험');
    }

    public function test_trace에_인자가_남지_않는다(): void
    {
        // 운영 설정에 기대지 않는다는 것을 보이려고, 인자를 적는 쪽으로 켜 둔다.
        $old = ini_set('zend.exception_ignore_args', '0');

        try {
            $secret = Str::random(64);

            try {
                $this->takesLongString($secret);
            } catch (RuntimeException $e) {
                // getTraceAsString() 은 앞 15자를 적는다 — 이것을 쓰면 안 되는 이유.
                $this->assertStringContainsString(substr($secret, 0, 15), $e->getTraceAsString());

                $trace = $this->sentPayload($e)['trace'];
            }

            $this->assertStringNotContainsString(substr($secret, 0, 15), $trace);
            $this->assertStringContainsString(self::class.'->takesLongString()', $trace);
            $this->assertMatchesRegularExpression('/^#0 .+\(\d+\): .+\(\)$/m', $trace);
        } finally {
            ini_set('zend.exception_ignore_args', $old === false ? '1' : $old);
        }
    }
}
