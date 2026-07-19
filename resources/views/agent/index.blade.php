@extends('layouts.app')
@section('title', '구매 대행자 콘솔 — 망고샵')

@section('content')
<div class="page-head"><div class="container"><h1>구매 대행자 콘솔</h1></div></div>

<div class="container" style="padding:24px 20px 60px;max-width:1000px">

    {{-- 요약 --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:26px">
        <div class="form-card" style="text-align:center;padding:20px">
            <div class="muted" style="font-size:13px">누적 캐시백</div>
            <div style="font-size:26px;font-weight:900;color:var(--navy-800);margin-top:6px">{{ number_format($totalCashback) }}<span style="font-size:15px">원</span></div>
        </div>
        <div class="form-card" style="text-align:center;padding:20px">
            <div class="muted" style="font-size:13px">대행 주문</div>
            <div style="font-size:26px;font-weight:900;margin-top:6px">{{ number_format($orderCount) }}<span style="font-size:15px">건</span></div>
        </div>
        <div class="form-card" style="text-align:center;padding:20px">
            <div class="muted" style="font-size:13px">캐시백율</div>
            <div style="font-size:26px;font-weight:900;color:var(--red);margin-top:6px">{{ rtrim(rtrim(number_format($user->cashback_rate,2),'0'),'.') }}<span style="font-size:15px">%</span></div>
        </div>
        <div class="form-card" style="text-align:center;padding:20px">
            <div class="muted" style="font-size:13px">등록 구매자</div>
            <div style="font-size:26px;font-weight:900;margin-top:6px">{{ number_format($buyers->count()) }}<span style="font-size:15px">명</span></div>
        </div>
    </div>

    {{-- 구매자(소매처) 명부 --}}
    <div class="form-card" style="margin-bottom:22px">
        <h3><x-icon name="user"/> 구매자(소매처) 명부</h3>
        <p class="muted" style="font-size:13px;margin:-4px 0 14px">대행 주문 시 이 명부에서 구매자를 선택할 수 있습니다. 1명의 대행자가 여러 구매자를 관리합니다.</p>

        <form method="POST" action="{{ route('agent.buyers.store') }}" style="display:grid;grid-template-columns:1.2fr 1fr 1fr 1.4fr auto;gap:8px;align-items:end;margin-bottom:16px">
            @csrf
            <div class="field" style="margin:0"><label>이름 <span class="req">*</span></label><input type="text" name="name" class="input" required></div>
            <div class="field" style="margin:0"><label>사업자번호</label><input type="text" name="biz_no" class="input" placeholder="000-00-00000"></div>
            <div class="field" style="margin:0"><label>전화번호</label><input type="text" name="phone" class="input" placeholder="010-0000-0000"></div>
            <div class="field" style="margin:0"><label>메모</label><input type="text" name="memo" class="input"></div>
            <button type="submit" class="btn btn-primary" style="white-space:nowrap">추가</button>
        </form>

        <table class="table" style="width:100%;border-collapse:collapse">
            <thead><tr style="border-bottom:2px solid var(--line);text-align:left">
                <th style="padding:10px 8px">이름</th><th>사업자번호</th><th>전화번호</th><th>메모</th><th></th>
            </tr></thead>
            <tbody>
                @forelse($buyers as $b)
                    <tr style="border-bottom:1px solid var(--line)">
                        <td style="padding:10px 8px;font-weight:600">{{ $b->name }}</td>
                        <td>{{ $b->biz_no ?: '-' }}</td>
                        <td>{{ $b->phone ?: '-' }}</td>
                        <td class="muted">{{ $b->memo ?: '-' }}</td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('agent.buyers.destroy', $b) }}" onsubmit="return confirm('삭제하시겠습니까?')" style="display:inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm" type="submit">삭제</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:var(--slate-400);padding:26px">등록된 구매자가 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 캐시백 내역 --}}
    <div class="form-card">
        <h3><x-icon name="coin"/> 캐시백 내역</h3>
        <table class="table" style="width:100%;border-collapse:collapse">
            <thead><tr style="border-bottom:2px solid var(--line);text-align:left">
                <th style="padding:10px 8px">일시</th><th>주문번호</th><th>구매자</th><th style="text-align:right">주문금액</th><th style="text-align:right">캐시백</th><th>상태</th>
            </tr></thead>
            <tbody>
                @forelse($cashbacks as $c)
                    <tr style="border-bottom:1px solid var(--line)">
                        <td style="padding:10px 8px" class="muted">{{ $c->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $c->order->order_no ?? '-' }}</td>
                        <td>{{ $c->buyer_name ?: '-' }}</td>
                        <td style="text-align:right">{{ number_format($c->order_amount) }}원</td>
                        <td style="text-align:right;font-weight:800;color:var(--navy-800)">+{{ number_format($c->amount) }}원</td>
                        <td><span class="badge {{ $c->status === 'paid' ? 'badge-new' : 'badge-plan' }}">{{ $c->status === 'paid' ? '적립완료' : $c->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;color:var(--slate-400);padding:26px">캐시백 내역이 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="muted" style="font-size:12.5px;margin-top:14px">※ 캐시백은 주문 접수 시 적립금으로 자동 지급되며, <a href="{{ route('mypage.points') }}" style="color:var(--navy-800);font-weight:600">적립금 내역</a>에서 확인·사용할 수 있습니다.</p>
</div>
@endsection
