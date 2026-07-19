<?php

namespace App\Http\Controllers;

use App\Models\AgentBuyer;
use Illuminate\Http\Request;

/**
 * 구매 대행자 콘솔 — 구매자(소매처) 명부 관리 + 캐시백 내역.
 */
class AgentController extends Controller
{
    private function guard(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->isAgent(), 403, '구매 대행자 전용 페이지입니다.');

        return $user;
    }

    public function index(Request $request)
    {
        $user = $this->guard($request);

        $buyers = $user->buyers()->get();
        $cashbacks = $user->cashbacks()->with('order')->take(30)->get();
        $totalCashback = $user->cashbacks()->where('status', 'paid')->sum('amount');
        $orderCount = $user->cashbacks()->count();

        return view('agent.index', compact('user', 'buyers', 'cashbacks', 'totalCashback', 'orderCount'));
    }

    public function storeBuyer(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:50'],
            'biz_no' => ['nullable', 'string', 'max:30'],
            'phone'  => ['nullable', 'string', 'max:30'],
            'memo'   => ['nullable', 'string', 'max:200'],
        ]);
        $user->buyers()->create($data);

        return back()->with('ok', '구매자를 등록했습니다.');
    }

    public function updateBuyer(Request $request, AgentBuyer $buyer)
    {
        $user = $this->guard($request);
        abort_unless($buyer->agent_id === $user->id, 403);
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:50'],
            'biz_no' => ['nullable', 'string', 'max:30'],
            'phone'  => ['nullable', 'string', 'max:30'],
            'memo'   => ['nullable', 'string', 'max:200'],
        ]);
        $buyer->update($data);

        return back()->with('ok', '구매자 정보를 수정했습니다.');
    }

    public function destroyBuyer(Request $request, AgentBuyer $buyer)
    {
        $user = $this->guard($request);
        abort_unless($buyer->agent_id === $user->id, 403);
        $buyer->delete();

        return back()->with('ok', '구매자를 삭제했습니다.');
    }
}
