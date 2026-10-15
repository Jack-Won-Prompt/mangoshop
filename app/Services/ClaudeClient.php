<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Anthropic Claude Messages API 클라이언트 (관리자 SR 콘솔용).
 * - 대화 이력 + 첨부(이미지/PDF/텍스트)를 content 블록으로 구성해 호출.
 * - 키는 config('services.anthropic.key') (= .env ANTHROPIC_API_KEY).
 */
class ClaudeClient
{
    public function configured(): bool
    {
        return ! empty(config('services.anthropic.key'));
    }

    public function model(): string
    {
        return (string) config('services.anthropic.model', 'claude-sonnet-5');
    }

    /**
     * 대화 이력으로 응답 생성.
     *
     * @param  array  $history  [['role'=>'user|assistant','content'=>string,'attachments'=>[['path','mime','name'],...]], ...]
     * @return array{text:string, input_tokens:int, output_tokens:int}
     */
    public function chat(array $history, ?string $system = null): array
    {
        $messages = [];
        foreach ($history as $m) {
            $role = $m['role'] === 'assistant' ? 'assistant' : 'user';
            $blocks = $this->blocksFor($m, $role);
            if ($blocks) {
                $messages[] = ['role' => $role, 'content' => $blocks];
            }
        }

        $payload = [
            'model'      => $this->model(),
            'max_tokens' => (int) config('services.anthropic.max_tokens', 4096),
            'messages'   => $messages,
        ];
        if ($system) {
            $payload['system'] = $system;
        }

        $res = Http::withHeaders([
            'x-api-key'         => (string) config('services.anthropic.key'),
            'anthropic-version' => (string) config('services.anthropic.version', '2023-06-01'),
            'content-type'      => 'application/json',
            // 그림이 여러 장 붙은 대화는 보내는 데만도 시간이 걸린다.
            // 120초에서는 큰 대화가 끊겼다. 고치는 쪽(Agent)과 같은 300초로 맞춘다.
        ])->timeout(300)->post(rtrim((string) config('services.anthropic.base'), '/').'/v1/messages', $payload);

        if ($res->failed()) {
            $err = $res->json('error.message') ?: ('HTTP '.$res->status());

            throw new \RuntimeException('Claude API 오류: '.$err);
        }

        $text = collect($res->json('content', []))
            ->where('type', 'text')->pluck('text')->implode("\n");

        return [
            'text'          => trim($text),
            'input_tokens'  => (int) $res->json('usage.input_tokens', 0),
            'output_tokens' => (int) $res->json('usage.output_tokens', 0),
        ];
    }

    /** 한 메시지를 Anthropic content 블록 배열로 변환 */
    private function blocksFor(array $m, string $role): array
    {
        $blocks = [];
        $text = trim((string) ($m['content'] ?? ''));

        // 첨부는 사용자 메시지에만 포함
        if ($role === 'user') {
            foreach ((array) ($m['attachments'] ?? []) as $att) {
                // 세로로 긴 화면 캡처는 여러 장으로 잘라 보내므로 하나가 여러 블록이 될 수 있다
                foreach ($this->attachmentBlocks($att) as $block) {
                    $blocks[] = $block;
                }
            }
        }

        if ($text !== '') {
            $blocks[] = ['type' => 'text', 'text' => $text];
        } elseif ($role === 'user' && ! $blocks) {
            $blocks[] = ['type' => 'text', 'text' => '(첨부 파일을 검토해 주세요)'];
        }

        return $blocks;
    }

    /** 한 변이 이보다 길면 보내기 전에 줄인다 (API 는 8000 을 넘기면 통째로 거절한다) */
    private const IMAGE_MAX_EDGE = 1568;

    /** 긴 변이 짧은 변의 이 배수를 넘으면 세로로 긴 화면 캡처로 보고 잘라 보낸다 */
    private const IMAGE_SLICE_RATIO = 2.5;

    /** 잘라 보낼 때 최대 장수 — 너무 잘게 나누면 보내는 양만 늘어난다 */
    private const IMAGE_SLICE_MAX = 4;

    /**
     * 첨부 하나를 블록 여럿으로 바꾼다.
     *
     * 모바일 전체 화면 캡처는 860×17409 처럼 세로로 아주 길다.
     * 긴 변만 보고 줄이면 77×1568 이 되어 글씨를 전혀 읽을 수 없다.
     * 보내기는 하지만 읽히지 않으니 없는 것과 마찬가지다.
     * 그래서 그런 그림은 위에서부터 몇 조각으로 잘라, 각 조각을 읽을 수 있는 크기로 보낸다.
     *
     * @return array<int, array<string, mixed>>
     */
    private function attachmentBlocks(array $att): array
    {
        $mime = strtolower((string) ($att['mime'] ?? ''));
        $path = $att['path'] ?? null;

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)
            || ! $path || ! Storage::disk('local')->exists($path)) {
            $one = $this->attachmentBlock($att);

            return $one ? [$one] : [];
        }

        $raw = (string) Storage::disk('local')->get($path);
        $size = @getimagesizefromstring($raw);
        if (! $size) {
            $one = $this->attachmentBlock($att);

            return $one ? [$one] : [];
        }

        [$w, $h] = $size;
        $long = max($w, $h);
        $short = max(1, min($w, $h));

        if ($long / $short < self::IMAGE_SLICE_RATIO) {
            $one = $this->attachmentBlock($att);

            return $one ? [$one] : [];
        }

        $slices = $this->sliceTall($raw, $w, $h, $mime);
        if (! $slices) {
            $one = $this->attachmentBlock($att);

            return $one ? [$one] : [];
        }

        $name = (string) ($att['name'] ?? basename($path));
        $blocks = [['type' => 'text', 'text' => "[{$name} — 세로로 긴 화면이라 위에서부터 "
            .count($slices).'조각으로 나눠 올립니다]']];
        foreach ($slices as $s) {
            $blocks[] = ['type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => $s['mime'], 'data' => base64_encode($s['body'])]];
        }

        return $blocks;
    }

    /**
     * 세로로 긴 그림을 위에서부터 몇 조각으로 자른다.
     *
     * 조각마다 조금씩 겹치게 해, 자른 자리에 걸친 글이 어느 한쪽에서는 온전히 보이게 한다.
     *
     * @return array<int, array{body:string, mime:string}>
     */
    private function sliceTall(string $raw, int $w, int $h, string $mime): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return [];
        }
        $src = @imagecreatefromstring($raw);
        if (! $src) {
            return [];
        }

        $tall = $h >= $w;                       // 세로로 긴가, 가로로 긴가
        $long = $tall ? $h : $w;
        $short = $tall ? $w : $h;

        // 짧은 변을 1568 안쪽으로 맞췄을 때, 조각 하나가 담을 수 있는 긴 변의 길이
        $scale = min(1.0, self::IMAGE_MAX_EDGE / $short);
        $chunk = (int) floor(self::IMAGE_MAX_EDGE / $scale);
        if ($chunk < 1) {
            return [];
        }

        $count = min(self::IMAGE_SLICE_MAX, (int) ceil($long / $chunk));
        $overlap = (int) round($chunk * 0.06);   // 자른 자리에 걸친 글을 위해 조금 겹친다

        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $from = max(0, $i * $chunk - ($i > 0 ? $overlap : 0));
            $len = min($chunk + $overlap, $long - $from);
            if ($len <= 0) {
                break;
            }

            $dw = $tall ? $w : $len;
            $dh = $tall ? $len : $h;
            $tw = max(1, (int) round($dw * $scale));
            $th = max(1, (int) round($dh * $scale));

            $dst = @imagecreatetruecolor($tw, $th);
            if (! $dst) {
                return [];
            }
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src,
                0, 0,
                $tall ? 0 : $from, $tall ? $from : 0,
                $tw, $th, $dw, $dh);

            $fmt = $this->outputMime($mime);
            ob_start();
            match ($fmt) {
                'image/png'  => imagepng($dst, null, 6),
                'image/webp' => imagewebp($dst, null, 85),
                default      => imagejpeg($dst, null, 85),
            };
            $body = (string) ob_get_clean();
            if ($body === '') {
                return [];
            }
            $out[] = ['body' => $body, 'mime' => $fmt];
        }

        return $out;
    }

    /**
     * 내보낼 형식.
     *
     * 웹피(webp)를 쓸 수 있으면 그것으로 낸다. 화면 캡처처럼 글씨가 많은 그림에서
     * jpg 보다 글씨가 또렷하면서 png 보다 훨씬 작다. 투명한 자리도 지킨다.
     * 못 쓰는 서버에서는, 투명할 수 있는 png·gif 는 png 로, 나머지는 jpg 로 낸다.
     */
    private function outputMime(string $mime): string
    {
        if (function_exists('imagewebp')) {
            return 'image/webp';
        }

        return in_array($mime, ['image/png', 'image/gif'], true) ? 'image/png' : 'image/jpeg';
    }

    /**
     * 그림 첨부를 보낼 수 있는 크기로 줄여 블록으로 만든다.
     *
     * 큰 사진을 그대로 보내면 "한 변이 8000 픽셀을 넘는다" 며 요청 전체가 거절된다.
     * 지난 글까지 매번 다시 보내는 구조라, 한 장이 걸리면 그 대화는 그때부터 아무 말도
     * 주고받지 못한다. 실제로 그렇게 막힌 대화가 있었다.
     *
     * 1568 픽셀로 줄이는 까닭은 그보다 크게 보내도 받는 쪽에서 어차피 줄이기 때문이다.
     * 미리 줄이면 거절도 피하고 보내는 양도 준다.
     *
     * 줄이지 못하는 그림은 글로 바꿔 알린다. 한 장 때문에 대화가 멈추는 것보다 낫다.
     */
    private function imageBlock(string $path, string $mime, string $name): ?array
    {
        $raw = (string) Storage::disk('local')->get($path);
        $size = @getimagesizefromstring($raw);

        // 크기를 못 읽으면 줄일 수도 없다. 그대로 보내되 한도를 넘지 않는 것만.
        if (! $size) {
            return $this->rawImageBlock($raw, $mime);
        }

        [$w, $h] = $size;

        // 크기가 작아도 다시 저장해 본다.
        // 화면 캡처 png 는 한 장에 2~3MB 가 예사인데, 다시 저장하면 수백 KB 로 준다.
        // 지난 글까지 매번 다시 보내는 구조라, 그림이 쌓이면 보내는 양이 금방 커지고
        // 큰 요청은 제한 시간에 걸려 끊긴다. 실제로 한 대화가 12.75MB 까지 갔다.
        $small = $this->shrink($raw, $w, $h, $mime);

        // 다시 저장했는데 되레 커졌다면 원본이 낫다. 줄일 필요가 없던 그림만 해당한다.
        $fits = $w <= self::IMAGE_MAX_EDGE && $h <= self::IMAGE_MAX_EDGE;
        if ($fits && ($small === null || strlen($small['body']) >= strlen($raw))) {
            return $this->rawImageBlock($raw, $mime);
        }

        if ($small === null) {
            return [
                'type' => 'text',
                'text' => "[첨부 그림 '{$name}' 은 너무 커서({$w}×{$h}) 함께 보내지 못했습니다. "
                    .'내용을 글로 설명해 주시면 그에 맞춰 답하겠습니다.]',
            ];
        }

        return [
            'type'   => 'image',
            'source' => ['type' => 'base64', 'media_type' => $small['mime'], 'data' => base64_encode($small['body'])],
        ];
    }

    /** 줄이지 않고 그대로 — 다만 한도를 넘는 것은 보내지 않는다 */
    private function rawImageBlock(string $raw, string $mime): ?array
    {
        $size = @getimagesizefromstring($raw);
        if ($size && (max($size[0], $size[1]) > 8000)) {
            return null;   // 보내면 요청 전체가 거절된다
        }

        return [
            'type'   => 'image',
            'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => base64_encode($raw)],
        ];
    }

    /**
     * 긴 변을 기준으로 줄인다.
     *
     * @return array{body:string, mime:string}|null 줄이지 못하면 null
     */
    private function shrink(string $raw, int $w, int $h, string $mime): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $src = @imagecreatefromstring($raw);
        if (! $src) {
            return null;
        }

        // 이미 작은 그림은 크기를 그대로 두고 다시 저장만 한다
        $scale = min(1.0, self::IMAGE_MAX_EDGE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = @imagecreatetruecolor($nw, $nh);
        if (! $dst) {
            return null;
        }

        // 투명한 자리가 검게 되지 않도록
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $out = $this->outputMime($mime);

        ob_start();
        match ($out) {
            'image/png'  => imagepng($dst, null, 6),
            'image/webp' => imagewebp($dst, null, 85),
            default      => imagejpeg($dst, null, 85),
        };
        $body = (string) ob_get_clean();

        // imagedestroy 는 PHP 8 부터 하는 일이 없고 8.5 에서는 경고까지 난다. 부르지 않는다.
        if ($body === '') {
            return null;
        }

        return ['body' => $body, 'mime' => $out];
    }

    /** 첨부 파일 → image / document / text 블록 */
    private function attachmentBlock(array $att): ?array
    {
        $path = $att['path'] ?? null;
        $mime = strtolower((string) ($att['mime'] ?? ''));
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return $this->imageBlock($path, $mime, (string) ($att['name'] ?? basename($path)));
        }

        if ($mime === 'application/pdf') {
            return [
                'type'   => 'document',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => 'application/pdf',
                    'data'       => base64_encode(Storage::disk('local')->get($path)),
                ],
            ];
        }

        // 텍스트류(소스코드/로그/CSV 등)는 본문 텍스트로 전달
        if (str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml', 'application/csv'], true)) {
            $name = $att['name'] ?? basename($path);
            $body = (string) Storage::disk('local')->get($path);
            if (strlen($body) > 200000) {
                $body = substr($body, 0, 200000)."\n…(생략)";
            }

            return ['type' => 'text', 'text' => "[첨부 파일: {$name}]\n```\n{$body}\n```"];
        }

        return ['type' => 'text', 'text' => '[첨부 파일: '.($att['name'] ?? basename($path)).' — 미리보기 불가 형식]'];
    }
}
