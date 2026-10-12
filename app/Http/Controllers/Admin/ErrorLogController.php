<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorEvent;
use Illuminate\Http\Request;

/**
 * 운영 오류 로그 — 자동 수집된 서버/화면 오류 조회·해결처리.
 */
class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $q = ErrorEvent::query()->with('thread')->latest('last_seen_at');

        if (in_array($request->get('source'), ['server', 'client'], true)) {
            $q->where('source', $request->get('source'));
        }
        if ($request->get('status') === 'resolved') {
            $q->whereNotNull('resolved_at');
        } elseif ($request->get('status') !== 'all') {
            $q->whereNull('resolved_at'); // 기본: 미해결만
        }

        $events = $q->paginate(20)->withQueryString();
        $openCount = ErrorEvent::whereNull('resolved_at')->count();

        return view('admin.errors.index', compact('events', 'openCount'));
    }

    public function show(ErrorEvent $event)
    {
        $event->load('thread');

        return view('admin.errors.show', compact('event'));
    }

    public function resolve(ErrorEvent $event)
    {
        $event->update(['resolved_at' => $event->resolved_at ? null : now()]);

        return back()->with('ok', $event->resolved_at ? '해결 처리했습니다.' : '미해결로 되돌렸습니다.');
    }

    public function destroy(ErrorEvent $event)
    {
        $event->delete();

        return redirect()->route('admin.errors.index')->with('ok', '오류 기록을 삭제했습니다.');
    }
}
