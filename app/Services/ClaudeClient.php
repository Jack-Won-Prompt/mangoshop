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

    /** 첨부 파일 → image / document / text 블록 */
    private function attachmentBlock(array $att): ?array
    {
        $path = $att['path'] ?? null;
        $mime = strtolower((string) ($att['mime'] ?? ''));
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $mime,
                    'data'       => base64_encode(Storage::disk('local')->get($path)),
                ],
            ];
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
