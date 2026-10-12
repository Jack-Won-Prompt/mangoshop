<?php

namespace App\Support;

use App\Models\ErrorEvent;
use App\Models\SrThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * 운영 오류(서버 예외 / 화면 스크립트 오류)를 DB에 적재·그룹핑하고,
 * 새 오류면 SR 요청을 자동 생성 + 담당자(FCM/이메일)에게 통지한다.
 *
 * 절대 예외를 밖으로 내보내지 않는다(기록이 사이트를 깨뜨리면 안 된다).
 * 코드 수정/배포 같은 실행은 하지 않는다 — 수집·정리·통지까지만.
 */
class ErrorReporter
{
    /** 서버 예외 기록(응답 후 실행). */
    public static function captureServer(Throwable $e, ?Request $request = null): void
    {
        try {
            if (! SupportWorksReporter::reportable($e)) {
                return;
            }
            $p = SupportWorksReporter::payload($e, $request);
            $data = [
                'source'     => 'server',
                'exception'  => $p['exception'],
                'message'    => $p['message'],
                'file'       => $p['file'],
                'line'       => $p['line'],
                'url'        => $p['url'],
                'method'     => $request?->method(),
                'user_id'    => optional($request?->user())->id,
                'user_agent' => Str::limit((string) $request?->userAgent(), 380, ''),
                'trace'      => $p['trace'],
            ];
            $run = fn () => self::record($data);
            app()->runningInConsole() ? $run() : app()->terminating($run);
        } catch (Throwable) {
            // 기록 실패는 무시
        }
    }

    /** 화면(JS) 오류 기록. 외부 입력이므로 길이·형식을 자른다. */
    public static function captureClient(array $in, ?Request $request = null): void
    {
        try {
            $msg = SupportWorksReporter::maskMessagePublic(trim((string) ($in['message'] ?? '')));
            if ($msg === '') {
                return;
            }
            self::record([
                'source'     => 'client',
                'exception'  => Str::limit((string) ($in['name'] ?? 'Error'), 60, ''),
                'message'    => mb_substr($msg, 0, 2000),
                'file'       => Str::limit((string) ($in['file'] ?? ''), 480, '') ?: null,
                'line'       => is_numeric($in['line'] ?? null) ? (int) $in['line'] : null,
                'url'        => Str::limit((string) ($in['url'] ?? ''), 480, '') ?: null,
                'method'     => 'GET',
                'user_id'    => optional($request?->user())->id,
                'user_agent' => Str::limit((string) $request?->userAgent(), 380, ''),
                'trace'      => mb_substr((string) ($in['stack'] ?? ''), 0, 8000) ?: null,
            ]);
        } catch (Throwable) {
            // 무시
        }
    }

    /** 공통: 그룹핑 upsert + 신규면 SR 생성·통지. */
    private static function record(array $d): void
    {
        $fp = md5(($d['source'] ?? 'server').'|'.($d['exception'] ?? '').'|'.($d['file'] ?? '').'|'.($d['line'] ?? '').'|'.self::normalize($d['message'] ?? ''));

        $existing = ErrorEvent::where('fingerprint', $fp)->whereNull('resolved_at')->first();

        if ($existing) {
            $existing->increment('count');
            $existing->update(['last_seen_at' => now(), 'url' => $d['url'] ?? $existing->url]);
            // 같은 오류가 지속되면 1시간에 한 번만 재통지
            if (! $existing->notified_at || $existing->notified_at->lt(now()->subHour())) {
                self::notify($existing, repeat: true);
                $existing->update(['notified_at' => now()]);
            }

            return;
        }

        $event = ErrorEvent::create(array_merge($d, [
            'fingerprint'  => $fp,
            'count'        => 1,
            'last_seen_at' => now(),
        ]));

        // 심각 오류 → SR 요청 자동 생성(담당자/Agent가 수정·배포에 쓸 컨텍스트)
        $thread = self::makeSrThread($event);
        if ($thread) {
            $event->update(['sr_thread_id' => $thread->id]);
        }

        self::notify($event, repeat: false);
        $event->update(['notified_at' => now()]);
    }

    private static function normalize(string $message): string
    {
        $m = preg_replace('/\d+/', '#', mb_strtolower($message));          // 숫자/ID 제거
        $m = preg_replace('/\s+/', ' ', (string) $m);

        return mb_substr(trim((string) $m), 0, 200);
    }

    private static function makeSrThread(ErrorEvent $e): ?SrThread
    {
        try {
            $thread = SrThread::create([
                'user_id'         => null,
                'title'           => '[오류] '.Str::limit(str_replace(["\n", "\r"], ' ', (string) $e->message), 34, '…'),
                'model'           => config('services.anthropic.model'),
                'last_message_at' => now(),
            ]);

            $where = $e->source === 'client' ? '화면 스크립트(브라우저)' : '서버';
            $body = "[자동 수집된 운영 오류]\n".
                    "구분: {$where}\n".
                    '종류: '.($e->exception ?: '-')."\n".
                    "메시지: {$e->message}\n".
                    ($e->file ? '위치: '.$e->file.($e->line ? ':'.$e->line : '')."\n" : '').
                    ($e->url ? '주소: '.$e->url."\n" : '').
                    ($e->method ? '메서드: '.$e->method."\n" : '').
                    "\n[스택]\n".Str::limit((string) $e->trace, 4000, "\n…");

            $thread->messages()->create(['role' => 'user', 'content' => $body]);

            return $thread;
        } catch (Throwable) {
            return null;
        }
    }

    private static function notify(ErrorEvent $e, bool $repeat): void
    {
        $where = $e->source === 'client' ? '화면 스크립트' : '서버';
        $title = ($repeat ? '🔁 ' : '🚨 ').'운영 오류 ('.$where.')';
        $body = Str::limit((string) $e->message, 70).($e->count > 1 ? " · {$e->count}회" : '');

        try {
            AdminPush::toAdmins($title, $body, [
                'type'      => 'error_event',
                'event_id'  => (string) $e->id,
                'thread_id' => (string) ($e->sr_thread_id ?? ''),
            ]);
        } catch (Throwable) {
        }

        $emails = collect(config('site.inquiry_emails', []))->filter()->all();
        if ($emails) {
            try {
                $link = $e->sr_thread_id ? route('admin.sr.show', $e->sr_thread_id) : route('admin.errors.index');
                $text = "운영 오류가 발생했습니다.\n\n".
                        "구분: {$where}\n".
                        '종류: '.($e->exception ?: '-')."\n".
                        "메시지: {$e->message}\n".
                        ($e->file ? '위치: '.$e->file.($e->line ? ':'.$e->line : '')."\n" : '').
                        ($e->url ? '주소: '.$e->url."\n" : '').
                        "발생횟수: {$e->count}\n\n".
                        "바로가기: {$link}\n";
                Mail::raw($text, function ($m) use ($emails, $where) {
                    $m->to($emails)->subject('[망고샵 오류] '.$where.' 오류 발생');
                });
            } catch (Throwable) {
            }
        }
    }
}
