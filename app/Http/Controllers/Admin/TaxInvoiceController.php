<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TaxInvoice;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Illuminate\Http\Request;

class TaxInvoiceController extends Controller
{
    public function __construct(private TaxInvoiceIssueService $service) {}

    /** 발행 이력 목록 */
    public function index(Request $request)
    {
        $q = TaxInvoice::with(['order', 'user'])->latest();

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('q')) {
            $kw = trim((string) $request->string('q'));
            $q->where(function ($w) use ($kw) {
                $w->where('receiver_corp_name', 'like', "%{$kw}%")
                    ->orWhere('receiver_corp_num', 'like', "%{$kw}%")
                    ->orWhere('mgt_key', 'like', "%{$kw}%")
                    ->orWhere('nts_confirm_num', 'like', "%{$kw}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_no', 'like', "%{$kw}%"));
            });
        }

        $invoices = $q->paginate(20)->withQueryString();
        $stats = [
            'issued'    => TaxInvoice::whereIn('status', ['issued', 'simulated'])->count(),
            'cancelled' => TaxInvoice::where('status', 'cancelled')->count(),
            'failed'    => TaxInvoice::where('status', 'failed')->count(),
            'amount'    => (int) TaxInvoice::whereIn('status', ['issued', 'simulated'])->sum('total_amount'),
        ];

        return view('admin.tax-invoices.index', compact('invoices', 'stats'));
    }

    /** 주문에 대한 세금계산서 발행 */
    public function issue(Request $request, Order $order)
    {
        try {
            $ti = $this->service->issueForOrder($order, $request->input('memo'));
            $msg = $ti->status === 'simulated'
                ? '세금계산서가 발행되었습니다. (시뮬레이트 모드 — 실제 팝빌 발행 아님)'
                : '전자세금계산서가 발행되었습니다.';

            return back()->with('ok', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', '발행 실패: '.$e->getMessage());
        }
    }

    /** 발행 취소 */
    public function cancel(Request $request, TaxInvoice $taxInvoice)
    {
        try {
            $this->service->cancel($taxInvoice, $request->input('memo', '발행취소'));

            return back()->with('ok', '세금계산서가 취소되었습니다.');
        } catch (\Throwable $e) {
            return back()->with('error', '취소 실패: '.$e->getMessage());
        }
    }

    /** 팝빌 문서 팝업(원본 보기) */
    public function popup(TaxInvoice $taxInvoice)
    {
        try {
            $url = $this->service->popupUrl($taxInvoice);
            if (! $url) {
                return back()->with('error', '시뮬레이트 발행건은 팝빌 원본이 없습니다.');
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
