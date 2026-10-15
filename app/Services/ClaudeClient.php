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
        ])->timeout(120)->post(rtrim((string) config('services.anthropic.base'), '/').'/v1/messages', $payload);

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
                $block = $this->attachmentBlock($att);
                if ($block) {
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
        if ($w <= self::IMAGE_MAX_EDGE && $h <= self::IMAGE_MAX_EDGE) {
            return $this->rawImageBlock($raw, $mime);
        }

        $small = $this->shrink($raw, $w, $h, $mime);
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

        $scale = self::IMAGE_MAX_EDGE / max($w, $h);
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

        // gif 는 색을 적게 쓰므로 png 로 내보낸다. webp 를 못 쓰는 서버도 있어 확인한다.
        $out = $mime;
        if ($out === 'image/gif' || ($out === 'image/webp' && ! function_exists('imagewebp'))) {
            $out = 'image/png';
        }

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

        return ['body' => $body, 'mime' => $out === 'image/gif' ? 'image/png' : $out];
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
