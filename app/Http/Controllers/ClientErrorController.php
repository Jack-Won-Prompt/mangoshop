<?php

namespace App\Http\Controllers;

use App\Support\ErrorReporter;
use Illuminate\Http\Request;

/**
 * 화면(브라우저) 스크립트 오류 수집 엔드포인트. 공개·CSRF 제외·레이트리밋.
 * 외부 입력이므로 크기를 자르고, 기록은 ErrorReporter가 마스킹·그룹핑한다.
 */
class ClientErrorController extends Controller
{
    public function store(Request $request)
    {
        $in = $request->only(['name', 'message', 'file', 'line', 'url', 'stack']);

        // 과도한 페이로드 차단
        foreach (['name', 'message', 'file', 'url'] as $k) {
            if (isset($in[$k])) {
                $in[$k] = mb_substr((string) $in[$k], 0, 2000);
            }
        }
        if (isset($in['stack'])) {
            $in['stack'] = mb_substr((string) $in['stack'], 0, 10000);
        }

        if (trim((string) ($in['message'] ?? '')) !== '') {
            ErrorReporter::captureClient($in, $request);
        }

        return response()->noContent(); // 204
    }
}
