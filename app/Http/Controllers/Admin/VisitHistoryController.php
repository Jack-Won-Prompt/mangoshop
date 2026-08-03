<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * 방문이력 — 웹/앱 방문(페이지 접속)·상품검색·상품조회·로그인 통합 조회.
 */
class VisitHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q = ActivityLog::with(['user', 'product'])->latest();

        $type = $request->get('type');
        if (array_key_exists($type, ActivityLog::TYPES)) {
            $q->where('type', $type);
        }
        if (in_array($request->get('platform'), ['web', 'app'], true)) {
            $q->where('platform', $request->get('platform'));
        }
        if ($request->filled('q')) {
            $kw = trim((string) $request->string('q'));
            $q->where(function ($w) use ($kw) {
                $w->where('keyword', 'like', "%{$kw}%")
                    ->orWhere('product_name', 'like', "%{$kw}%")
                    ->orWhere('email', 'like', "%{$kw}%")
                    ->orWhere('path', 'like', "%{$kw}%")
                    ->orWhere('ip', 'like', "%{$kw}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$kw}%")->orWhere('email', 'like', "%{$kw}%"));
            });
        }

        $logs = $q->paginate(40)->withQueryString();

        $today = now()->toDateString();
        $stats = [
            'today_visit'  => ActivityLog::where('type', 'visit')->whereDate('created_at', $today)->count(),
            'today_search' => ActivityLog::where('type', 'search')->whereDate('created_at', $today)->count(),
            'today_view'   => ActivityLog::where('type', 'product_view')->whereDate('created_at', $today)->count(),
            'today_total'  => ActivityLog::whereDate('created_at', $today)->count(),
        ];

        // 인기 검색어 / 인기 조회 상품 (최근 7일)
        $since = now()->subDays(7);
        $topKeywords = ActivityLog::where('type', 'search')->where('created_at', '>=', $since)
            ->whereNotNull('keyword')
            ->selectRaw('keyword, COUNT(*) as cnt')->groupBy('keyword')->orderByDesc('cnt')->limit(10)->get();
        $topProducts = ActivityLog::where('type', 'product_view')->where('created_at', '>=', $since)
            ->whereNotNull('product_name')
            ->selectRaw('product_name, COUNT(*) as cnt')->groupBy('product_name')->orderByDesc('cnt')->limit(10)->get();

        return view('admin.visit-history.index', compact('logs', 'stats', 'topKeywords', 'topProducts'));
    }
}
