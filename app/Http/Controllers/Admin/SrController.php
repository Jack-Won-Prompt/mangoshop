<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SrMessage;
use App\Models\SrThread;
use App\Services\ClaudeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * SR 관리 — Claude API 기반 관리자 콘솔(질의/첨부 분석/요청 정리).
 * 좌측: 스레드 목록 / 우측: 채팅. 데이터는 sr_threads / sr_messages.
 */
class SrController extends Controller
{
    public function __construct(private ClaudeClient $claude) {}

    public function index(Request $request)
    {
        $threads = SrThread::orderByDesc('last_message_at')->orderByDesc('id')->get();
        $thread = $threads->first();
        $messages = $thread ? $thread->messages()->get() : collect();

        return view('admin.sr.index', [
            'threads'    => $threads,
            'thread'     => $thread,
            'messages'   => $messages,
            'configured' => $this->claude->configured(),
            'model'      => $this->claude->model(),
        ]);
    }

    public function show(Request $request, SrThread $thread)
    {
        $threads = SrThread::orderByDesc('last_message_at')->orderByDesc('id')->get();

        return view('admin.sr.index', [
            'threads'    => $threads,
            'thread'     => $thread,
            'messages'   => $thread->messages()->get(),
            'configured' => $this->claude->configured(),
            'model'      => $this->claude->model(),
        ]);
    }

    public function store(Request $request)
    {
        $thread = SrThread::create([
            'user_id'         => $request->user()->id,
            'title'           => '새 요청',
            'model'           => $this->claude->model(),
            'last_message_at' => now(),
        ]);

        return redirect()->route('admin.sr.show', $thread);
    }

    public function destroy(SrThread $thread)
    {
        Storage::disk('local')->deleteDirectory('sr/'.$thread->id);
        $thread->delete();

        return redirect()->route('admin.sr.index')->with('ok', '요청이 삭제되었습니다.');
    }

    /** 사용자 메시지 전송 → Claude 응답 저장 (AJAX JSON) */
    public function message(Request $request, SrThread $thread)
    {
        $request->validate([
            'content'  => ['nullable', 'string', 'max:50000'],
            'files'    => ['nullable', 'array', 'max:8'],
            'files.*'  => ['file', 'max:20480', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,text/csv,text/markdown,application/json,application/xml,text/xml'],
        ], [
            'files.*.max'       => '첨부 파일은 20MB 이하만 올릴 수 있습니다.',
            'files.*.mimetypes' => '이미지·PDF·텍스트(코드/로그/CSV) 파일만 첨부할 수 있습니다.',
        ]);

        if (! $this->claude->configured()) {
            return response()->json(['error' => 'Claude API 키가 설정되지 않았습니다(.env ANTHROPIC_API_KEY).'], 422);
        }

        $content = trim((string) $request->input('content', ''));
        $attachments = $this->storeAttachments($request, $thread);

        if ($content === '' && ! $attachments) {
            return response()->json(['error' => '내용이나 첨부 파일을 입력해 주세요.'], 422);
        }

        $userMsg = $thread->messages()->create([
            'role'        => 'user',
            'content'     => $content,
            'attachments' => $attachments ?: null,
        ]);

        // 스레드 제목 자동 설정(첫 사용자 메시지 기준)
        if ($thread->title === '새 요청' && $content !== '') {
            $thread->title = Str::limit(str_replace(["\n", "\r"], ' ', $content), 40, '…');
        }

        try {
            $history = $thread->messages()->get()
                ->map(fn ($m) => [
                    'role'        => $m->role,
                    'content'     => $m->content,
                    'attachments' => $m->attachments ?? [],
                ])->all();

            $result = $this->claude->chat($history, $this->systemPrompt());
        } catch (\Throwable $e) {
            // 실패 시 사용자 메시지는 남기고 오류 반환
            $thread->update(['last_message_at' => now()]);

            return response()->json(['error' => $e->getMessage()], 502);
        }

        $assistant = $thread->messages()->create([
            'role'          => 'assistant',
            'content'       => $result['text'],
            'tokens_in'     => $result['input_tokens'],
            'tokens_out'    => $result['output_tokens'],
        ]);
        $thread->update(['last_message_at' => now()]);

        return response()->json([
            'ok'        => true,
            'title'     => $thread->title,
            'user'      => $this->present($userMsg),
            'assistant' => $this->present($assistant),
        ]);
    }

    /** 첨부 파일 다운로드/미리보기 (관리자 전용) */
    public function attachment(Request $request, SrThread $thread, int $message, int $idx)
    {
        $msg = $thread->messages()->findOrFail($message);
        $att = ($msg->attachments ?? [])[$idx] ?? abort(404);
        abort_unless(str_starts_with($att['path'], 'sr/'.$thread->id.'/'), 403);
        abort_unless(Storage::disk('local')->exists($att['path']), 404);

        return response(Storage::disk('local')->get($att['path']), 200, [
            'Content-Type'        => $att['mime'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($att['name'] ?? 'file').'"',
        ]);
    }

    /* ===== 내부 ===== */

    private function storeAttachments(Request $request, SrThread $thread): array
    {
        $out = [];
        foreach ((array) $request->file('files', []) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'bin'));
            $name = now()->format('Ymd_His').'_'.Str::lower(Str::random(6)).'.'.$ext;
            $path = $file->storeAs('sr/'.$thread->id, $name, 'local');
            $out[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        return $out;
    }

    private function present(SrMessage $m): array
    {
        $atts = [];
        foreach (($m->attachments ?? []) as $i => $a) {
            $atts[] = [
                'name'  => $a['name'] ?? 'file',
                'mime'  => $a['mime'] ?? '',
                'url'   => route('admin.sr.attachment', [$m->sr_thread_id, $m->id, $i]),
                'image' => str_starts_with((string) ($a['mime'] ?? ''), 'image/'),
            ];
        }

        return [
            'id'          => $m->id,
            'role'        => $m->role,
            'content'     => (string) $m->content,
            'attachments' => $atts,
            'time'        => $m->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
너는 "망고샵"(수입 과일 도매·소매 오픈마켓) 운영 관리자를 돕는 사내 어시스턴트다.
관리자가 올리는 SR(서비스 요청 — 신규/수정 기능, 버그, 운영 문의)과 첨부 파일(이미지/PDF/문서)을 분석해
한국어로 쉽고 명확하게, 비개발자도 이해할 수 있는 일반 업무 언어로 답한다.

원칙:
- 요청을 먼저 한두 줄로 요약한다.
- 코드, 파일 경로, 함수명, diff, 명령어, 프로그래밍 용어 등 기술적 세부사항은 "절대" 쓰지 않는다. 코드블록도 넣지 않는다.
- 대신 "무엇을 / 어디에서 / 어떻게 보이거나 동작하게" 되는지를 업무/기능 관점에서 설명한다.
- 선택지가 있으면 장단점을 쉬운 말로 제시하고, 예상 효과·주의할 점·필요한 결정사항을 안내한다.
- 불확실하면 추측하지 말고 무엇을 더 확인해야 하는지 쉬운 말로 되묻는다.
- 실제 구현·반영은 개발 담당자가 처리한다는 전제로, 관리자가 의사결정에 필요한 내용 위주로 돕는다.
TXT;
    }
}
