<?php

namespace App\Services;

use App\Models\AgentTask;
use App\Models\ErrorEvent;
use App\Models\SrThread;
use App\Models\Setting;
use Throwable;

/**
 * Agent 가 처리할 일감을 접수한다. 접수 실패해도 본래 업무를 막지 않는다.
 */
class AgentIntake
{
    /** 운영 오류 접수 — 같은 원인은 하루 한 번만 */
    public static function error(ErrorEvent $event): void
    {
        try {
            if (! Setting::bool('agent_enabled') || ! Setting::bool('agent_error_enabled')) {
                return;
            }

            // Agent 자신이 낸 오류는 접수하지 않는다(무한 되돌이 방지)
            $where = mb_strtolower((string) $event->file.' '.(string) $event->trace);
            foreach (['agentworker', 'agentfixer', 'agentintake', 'agenttask', 'agenthook', 'agentworkcommand'] as $mine) {
                if (str_contains($where, $mine)) {
                    return;
                }
            }

            $key = 'err:'.$event->fingerprint;
            $twin = AgentTask::where('dedupe_key', $key)
                ->where('created_at', '>=', now()->subDay())
                ->latest('id')->first();
            if ($twin) {
                self::wake($twin);

                return;
            }

            $task = AgentTask::create([
                'type' => 'error',
                'ref_id' => $event->id,
                'dedupe_key' => $key,
                'payload' => [
                    'class' => $event->exception,
                    'message' => mb_substr((string) $event->message, 0, 500),
                    'file' => $event->file,
                    'line' => $event->line,
                    'url' => $event->url,
                ],
                'status' => 'pending',
            ]);

            AgentHook::notify($task);
        } catch (Throwable $ignored) {
        }
    }

    /** 아직 대기 중인 일감이면 다시 알려 일꾼을 깨운다 */
    private static function wake(AgentTask $task): void
    {
        if ($task->status === 'pending') {
            AgentHook::notify($task);
        }
    }

    /** SR 접수 */
    public static function sr(SrThread $thread): void
    {
        try {
            if (! Setting::bool('agent_enabled') || ! Setting::bool('agent_sr_enabled')) {
                return;
            }

            $key = 'sr:'.$thread->id;
            $twin = AgentTask::where('dedupe_key', $key)->latest('id')->first();
            if ($twin && in_array($twin->status, ['pending', 'working'], true)) {
                self::wake($twin);   // 아직 처리 중이면 중복 생성 말고 깨우기만

                return;
            }

            $task = AgentTask::create([
                'type' => 'sr',
                'ref_id' => $thread->id,
                'dedupe_key' => $key,
                'payload' => [
                    'title' => $thread->title,
                    'status' => $thread->apply_status,
                ],
                'status' => 'pending',
            ]);

            AgentHook::notify($task);
        } catch (Throwable $ignored) {
        }
    }
}
