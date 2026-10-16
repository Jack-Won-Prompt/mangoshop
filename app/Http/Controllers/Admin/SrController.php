<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentTask;
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

        $this->autoRequestApply($thread, $assistant, $request->user());

        return response()->json([
            'ok'           => true,
            'title'        => $thread->title,
            'user'         => $this->present($userMsg),
            'assistant'    => $this->present($assistant),
            'apply_status' => $thread->fresh()->apply_status,
        ]);
    }

    /**
     * 어시스턴트가 "반영할 만하다" 고 답하면 그 자리에서 반영을 건다.
     *
     * 예전에는 관리자가 버튼을 눌러야 했다. 그 버튼이 하던 판단을 어시스턴트가 이미
     * 답마다 하고 있으므로(반영 가능 여부), 같은 판단을 두 번 거칠 까닭이 없다.
     * 아직 되묻는 중이거나 단순 안내인 답은 반영 가능으로 표시되지 않아 그냥 지나간다.
     *
     * 버튼은 "요청 취소" 와 "완료 처리" 를 위해 그대로 둔다.
     */
    private function autoRequestApply(SrThread $thread, SrMessage $assistant, $user): void
    {
        if (! $assistant->canApply() || $thread->apply_status === 'requested') {
            return;
        }

        try {
            // 앞서 처리를 마친 일감이 있으면 다시 대기로 돌린다.
            // 대화가 이어져 새 지시가 들어왔다는 뜻이고, 접수 쪽은 같은 대화를 두 번
            // 받지 않으므로 여기서 풀어 주지 않으면 두 번째 요청이 영영 묻힌다.
            AgentTask::where('type', 'sr')->where('ref_id', $thread->id)
                ->whereIn('status', ['done', 'skipped', 'failed', 'needs_ops'])
                ->update(['status' => 'pending', 'attempts' => 0, 'result' => null, 'commit_hash' => null]);

            $thread->update(['apply_status' => 'requested', 'apply_requested_at' => now()]);
            $this->notifyApplyRequest($thread, $user);
            \App\Services\AgentIntake::sr($thread);
        } catch (\Throwable $e) {
            // 반영을 걸지 못해도 대화 자체는 그대로 남아야 한다
            report($e);
        }
    }

    /** 폴링: 특정 메시지 id 이후의 새 메시지 + 현재 반영 상태 (새로고침 없이 결과 수신) */
    public function poll(Request $request, SrThread $thread)
    {
        $after = (int) $request->get('after', 0);
        $messages = $thread->messages()->where('id', '>', $after)->get()
            ->map(fn ($m) => $this->present($m))->values();

        return response()->json([
            'messages'     => $messages,
            'apply_status' => $thread->apply_status,
        ]);
    }

    /** 개발 반영 요청 상태 변경 (request | cancel | done) — 실행이 아니라 담당자 처리용 표시+통지 */
    public function applyStatus(Request $request, SrThread $thread)
    {
        $data = $request->validate([
            'action' => ['required', 'in:request,cancel,done'],
            'note'   => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['action'] === 'request') {
            // 화면에서 버튼을 잠가 두지만, 주소를 직접 부르면 그것만으로는 막히지 않는다.
            // 어시스턴트가 "반영할 만하다" 고 답한 건만 담당자에게 넘긴다.
            // messages() 에 이미 orderBy('id') 가 걸려 있어 latest('id') 를 덧붙이면
            // 정렬이 덮이지 않고 뒤에 붙어, 가장 오래된 글(= 요청자의 첫 글)이 잡혔다.
            // 첫 글은 어시스턴트 답이 아니므로 이 관문은 언제나 막혀 있었다.
            $last = $thread->messages()->reorder('id', 'desc')->first();
            if (! $last || ! $last->canApply()) {
                return response()->json([
                    'ok'      => false,
                    'message' => '아직 반영할 내용이 정해지지 않았습니다. 어시스턴트와 대화를 이어가 주세요.',
                ], 422);
            }

            $thread->update([
                'apply_status'       => 'requested',
                'apply_note'         => $data['note'] ?? null,
                'apply_requested_at' => now(),
            ]);
            $this->notifyApplyRequest($thread, $request->user());
             \App\Services\AgentIntake::sr($thread);
        } elseif ($data['action'] === 'done') {
            $thread->update(['apply_status' => 'done']);
        } else { // cancel
            $thread->update(['apply_status' => 'none', 'apply_requested_at' => null]);
        }

        return response()->json([
            'ok'           => true,
            'apply_status' => $thread->apply_status,
            'requested_at' => optional($thread->apply_requested_at)->format('Y-m-d H:i'),
        ]);
    }

    /** 담당자(관리자 앱 FCM + 운영 알림 이메일)에게 반영 요청 통지 */
    private function notifyApplyRequest(SrThread $thread, $requester): void
    {
        $title = '🛠 개발 반영 요청';
        $body = Str::limit($thread->title, 50).' · 요청자 '.($requester->name ?? '관리자');

        try {
            \App\Support\AdminPush::toAdmins($title, $body, [
                'type'      => 'sr_apply',
                'thread_id' => (string) $thread->id,
            ]);
        } catch (\Throwable $e) {
            // 알림 실패는 요청 자체를 막지 않음
        }

        $emails = collect(config('site.inquiry_emails', []))->filter()->all();
        if ($emails) {
            try {
                $url = route('admin.sr.show', $thread);
                $text = "SR 콘솔에서 개발 반영이 요청되었습니다.\n\n".
                        "제목: {$thread->title}\n".
                        '요청자: '.($requester->name ?? '관리자')."\n".
                        '요청시각: '.now()->format('Y-m-d H:i')."\n".
                        ($thread->apply_note ? "메모: {$thread->apply_note}\n" : '').
                        "\n바로가기: {$url}\n";
                \Illuminate\Support\Facades\Mail::raw($text, function ($m) use ($emails, $thread) {
                    $m->to($emails)->subject('[망고샵 SR] 개발 반영 요청 — '.Str::limit($thread->title, 40));
                });
            } catch (\Throwable $e) {
                // 이메일 실패도 무시(FCM/기록은 유지)
            }
        }
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
            'content'     => $m->displayContent(),
            'can_apply'   => $m->canApply(),
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
- 방향이 정리되면, 관리자가 원할 경우 화면 상단의 "개발 반영 요청" 버튼을 눌러 담당자에게 반영을 요청할 수 있음을 한 줄로 안내해도 좋다(강요하지 말 것).

마지막 줄 표시(필수):
답변 맨 끝에 아래 둘 중 하나를 그대로 한 줄 붙인다. 이 표시는 관리자 화면에 보이지 않으며,
"개발 반영 요청" 버튼을 누를 수 있게 할지 판단하는 데만 쓴다. 다른 말을 덧붙이지 않는다.
- 개발자가 손봐서 반영할 수 있을 만큼 할 일이 또렷해졌으면: [[APPLY:YES]]
- 단순 안내·설명이거나, 아직 되묻는 중이거나, 반영할 것이 없으면: [[APPLY:NO]]
망설여지면 [[APPLY:NO]] 를 쓴다. 아직 정리되지 않은 것을 담당자에게 넘기지 않기 위함이다.
TXT;
    }
}
