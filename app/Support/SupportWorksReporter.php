<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 운영에서 난 예외를 SupportWorks 로 보낸다.
 *
 * 보고가 사이트를 망가뜨리면 안 된다. 그래서 예외를 절대 밖으로 내보내지 않고,
 * 응답을 돌려준 뒤에 보내고, 시간 제한을 짧게 둔다.
 */
class SupportWorksReporter
{
    /** 가려야 할 이름. 값이 그대로 남으면 오류 기록이 곧 자격증명 창고가 된다. */
    private const SECRET_KEYS = [
        'token', 'access_token', 'refresh_token', 'api_key', 'apikey',
        'secret', 'password', 'passwd', 'pw', 'signature', 'auth', 'key',
        'authorization', 'credential', 'credentials', 'bearer', 'otp', 'pin', 'session',
    ];

    private const MASK = '[숨김]';

    /** message 낱말 앞뒤에서 떼어 낼 문장부호. 가운데의 . / \ 는 건드리지 않는다. */
    private const EDGE_PUNCT = '\'"`()\[\]{}<>,.;:!?“”‘’«»';

    public static function report(Throwable $e, ?Request $request = null): void
    {
        try {
            $url = config('services.supportworks.error_url');
            $token = config('services.supportworks.error_token');

            if (! $url || ! $token) {
                return;
            }

            // 404 는 보내지 않는다. 고칠 것이 없고, 봇이 훑고 갈 때마다 쌓인다.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return;
            }

            $payload = [
                'level'     => 'error',
                'exception' => get_class($e),
                'message'   => mb_substr(self::maskMessage($e->getMessage()), 0, 2000),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'url'       => $request ? self::maskUrl($request) : null,
                'trace'     => mb_substr(self::trace($e), 0, 8000),
            ];

            // 응답을 돌려준 뒤에 보낸다. 콘솔에는 그 시점이 없으므로 바로 보낸다.
            $send = function () use ($url, $token, $payload) {
                try {
                    Http::withToken($token)->timeout(2)->connectTimeout(2)->post($url, $payload);
                } catch (Throwable) {
                    // 보고가 실패해도 사이트는 계속 돈다.
                }
            };

            app()->runningInConsole() ? $send() : app()->terminating($send);
        } catch (Throwable) {
            // 여기서 예외가 새어 나가면 예외 처리기 자체가 깨진다.
        }
    }

    /**
     * 요청 주소. 쿼리스트링은 이름으로, 경로는 생김새로 가린다.
     * 경로의 평범한 조각(/orders/123)은 남긴다 — 어느 화면인지 알아야 쓸모가 있다.
     */
    private static function maskUrl(Request $request): string
    {
        $segments = array_map(
            fn (string $seg) => self::looksLikeSecret(rawurldecode($seg)) ? self::MASK : $seg,
            explode('/', $request->getPathInfo())
        );

        $url = $request->getSchemeAndHttpHost().$request->getBaseUrl().implode('/', $segments);

        $query = self::maskQuery($request->query->all());

        if ($query !== []) {
            // 사람이 읽을 기록이므로 퍼센트 인코딩을 풀어 둔다.
            $url .= '?'.rawurldecode(http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        }

        return $url;
    }

    /** 이름이 목록에 걸리면 값만 가린다. 배열은 재귀로 들어간다. */
    private static function maskQuery(array $params): array
    {
        foreach ($params as $name => $value) {
            if (self::isSecretName((string) $name)) {
                $params[$name] = self::MASK;
            } elseif (is_array($value)) {
                $params[$name] = self::maskQuery($value);
            }
        }

        return $params;
    }

    /**
     * 이름 전체가 목록과 같거나(apiKey → apikey), 영숫자가 아닌 글자로 쪼갠 조각 하나가
     * 목록과 정확히 같을 때만 비밀로 본다. keyword·author·monkey 는 걸리지 않는다.
     */
    private static function isSecretName(string $name): bool
    {
        $lower = strtolower($name);

        if (in_array($lower, self::SECRET_KEYS, true)) {
            return true;
        }

        foreach (preg_split('/[^a-z0-9]+/', $lower, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            if (in_array($part, self::SECRET_KEYS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 오류 메시지. 예외 종류와 상관없이 낱말마다 생김새로 가린다.
     * QueryException 은 SQL 과 바인딩 값을 그대로 담기 때문에 필요하다.
     */
    private static function maskMessage(string $message): string
    {
        $parts = preg_split('/(\s+)/u', $message, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $message;
        }

        $edge = '['.self::EDGE_PUNCT.']*';

        foreach ($parts as $i => $word) {
            if ($word === '' || preg_match('/^\s+$/u', $word)) {
                continue;
            }

            if (preg_match('/^('.$edge.')(.*?)('.$edge.')$/us', $word, $m) && self::looksLikeSecret($m[2])) {
                $parts[$i] = $m[1].self::MASK.$m[3];
            }
        }

        return implode('', $parts);
    }

    /**
     * 스택. getTraceAsString() 은 인자를 앞 15자까지 적으므로 쓰지 않는다.
     * 파일·줄·클래스·함수만 모으고 인자는 아예 넣지 않는다.
     */
    private static function trace(Throwable $e): string
    {
        $lines = [];

        foreach ($e->getTrace() as $i => $frame) {
            $where = isset($frame['file'])
                ? $frame['file'].'('.($frame['line'] ?? '?').')'
                : '[internal function]';

            $call = ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '').'()';

            $lines[] = '#'.$i.' '.$where.': '.$call;
        }

        $lines[] = '#'.count($lines).' {main}';

        return implode("\n", $lines);
    }

    /** 토큰처럼 생긴 낱말: 20자 이상, 영숫자·_·- 로만. 경로와 message 가 함께 쓴다. */
    private static function looksLikeSecret(string $word): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{20,}$/', $word);
    }
}
