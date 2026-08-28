<?php

namespace App\Http\Controllers;

use App\Models\AgentBuyer;
use App\Models\AgentCashback;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $user = $request->user();
        $items = $user->cartItems()->with('product.brand')->get()
            ->filter(fn ($i) => $i->product !== null);

        // 분할배송(엑셀=주문서)은 장바구니 없이도 진입 가능
        $splitEntry = $request->query('mode') === 'split';
        if ($items->isEmpty() && ! $splitEntry) {
            return redirect()->route('cart.index')->with('error', '장바구니가 비어 있습니다.');
        }

        $summary = $items->isEmpty()
            ? ['subtotal' => 0, 'shipping' => 0, 'total' => 0, 'count' => 0]
            : CartController::summarize($items, $user);
        [$coupon, $couponDiscount, $couponError] = $this->resolveCoupon($user, $summary['subtotal']);

        // 보유(발행받은) 쿠폰 — 이미 적용된 것 제외
        $availableCoupons = $user->availableCoupons()
            ->filter(fn ($uc) => ! $coupon || $uc->coupon_id !== $coupon->id)->values();

        return view('order.checkout', [
            'items' => $items, 'summary' => $summary, 'user' => $user,
            'coupon' => $coupon, 'couponDiscount' => $couponDiscount, 'couponError' => $couponError,
            'availableCoupons' => $availableCoupons,
            'agentBuyers' => $user->isAgent() ? $user->buyers()->get() : collect(),
            'splitEntry' => $splitEntry,
        ]);
    }

    /** 쿠폰 적용 (세션에 코드 저장) */
    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        $user = $request->user();
        $subtotal = CartController::summarize(
            $user->cartItems()->with('product')->get()->filter(fn ($i) => $i->product !== null),
            $user
        )['subtotal'];

        $coupon = Coupon::findByCode($data['code']);
        if (! $coupon) {
            return back()->with('error', '존재하지 않는 쿠폰 코드입니다.');
        }
        [$ok, $msg] = $coupon->validateFor($user, $subtotal);
        if (! $ok) {
            return back()->with('error', $msg);
        }

        $request->session()->put('coupon_code', $coupon->code);

        return back()->with('ok', '쿠폰이 적용되었습니다. ('.number_format($coupon->discountFor($subtotal)).'원 할인)');
    }

    public function removeCoupon(Request $request)
    {
        $request->session()->forget('coupon_code');

        return back()->with('ok', '쿠폰이 해제되었습니다.');
    }

    /** 분할배송 엑셀(CSV) 양식 다운로드 */
    public function splitTemplate(\App\Services\SplitDeliveryService $svc)
    {
        return response($svc->template(), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="분할배송_양식.csv"',
        ]);
    }

    /** 분할배송 엑셀 업로드 → 파싱·검증 후 미리보기(JSON). 정상 시 세션 저장 */
    public function splitPreview(Request $request, \App\Services\SplitDeliveryService $svc)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $result = $svc->parse(file_get_contents($request->file('file')->getRealPath()), $request->user());

        if ($result['ok']) {
            $request->session()->put('split_shipments', $result['shipments']);
        } else {
            $request->session()->forget('split_shipments');
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // 분할배송(엑셀=주문서)
        if ($request->input('delivery_mode') === 'split') {
            return $this->storeSplit($request, $user);
        }

        $rules = [
            'receiver_name'  => ['required', 'string', 'max:50'],
            'receiver_phone' => ['required', 'string', 'max:30'],
            'postcode'       => ['nullable', 'string', 'max:10'],
            'address1'       => ['required', 'string', 'max:200'],
            'address2'       => ['nullable', 'string', 'max:200'],
            'memo'           => ['nullable', 'string', 'max:300'],
            'payment_method' => ['required', 'in:bank,toss,portone'],
            'depositor'      => ['required_if:payment_method,bank', 'nullable', 'string', 'max:50'],
            'bank'           => ['required_if:payment_method,bank', 'nullable', 'string', 'max:50'],
            'point_used'     => ['nullable', 'integer', 'min:0'],
        ];
        // 구매 대행자: 구매자(소매처) 정보 필수
        if ($user->isAgent()) {
            $rules['buyer_name']   = ['required', 'string', 'max:50'];
            $rules['buyer_biz_no'] = ['nullable', 'string', 'max:30'];
            $rules['buyer_phone']  = ['nullable', 'string', 'max:30'];
            $rules['save_buyer']   = ['nullable', 'boolean'];
        }
        $data = $request->validate($rules);

        $isPg = $data['payment_method'] !== 'bank';

        $items = $user->cartItems()->with(['product', 'option'])->get()
            ->filter(fn ($i) => $i->product !== null);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', '장바구니가 비어 있습니다.');
        }

        $summary = CartController::summarize($items, $user);
        [$coupon, $couponDiscount] = $this->resolveCoupon($user, $summary['subtotal']);

        // 적립금은 (상품금액 - 쿠폰할인) 까지만 사용 가능
        $pointCap = max(0, $summary['subtotal'] - $couponDiscount);
        $pointUsed = min((int) ($data['point_used'] ?? 0), $user->point, $pointCap);

        // 셀러별 하위주문 분할 생성 → 대표주문 반환
        // (판매자 알림은 결제완료 시 Order::markPaid 훅에서 자동 발송)
        $order = app(\App\Services\OrderPlacement::class)
            ->place($user, $items, $data, $isPg, $coupon, $couponDiscount, $pointUsed);

        $request->session()->forget('coupon_code');

        if ($isPg) {
            return redirect()->route('order.pay', $order);
        }

        return redirect()->route('order.complete', $order)->with('ok', '주문이 접수되었습니다.');
    }

    /** 세션 쿠폰 해석 → [?Coupon, discount, ?error] */
    private function resolveCoupon($user, int $subtotal): array
    {
        $code = session('coupon_code');
        if (! $code) {
            return [null, 0, null];
        }
        $coupon = Coupon::findByCode($code);
        if (! $coupon) {
            session()->forget('coupon_code');

            return [null, 0, null];
        }
        [$ok, $msg] = $coupon->validateFor($user, $subtotal);
        if (! $ok) {
            return [null, 0, $msg];
        }

        return [$coupon, $coupon->discountFor($subtotal), null];
    }

    /** 분할배송 주문 처리 (엑셀=주문서, 세션의 검증된 수령처 사용) */
    private function storeSplit(Request $request, $user)
    {
        $request->validate([
            'payment_method' => ['required', 'in:bank,toss,portone'],
            'depositor'      => ['required_if:payment_method,bank', 'nullable', 'string', 'max:50'],
            'bank'           => ['required_if:payment_method,bank', 'nullable', 'string', 'max:50'],
        ]);

        $shipments = $request->session()->get('split_shipments');
        if (empty($shipments)) {
            return redirect()->route('order.checkout')->with('error', '분할배송 엑셀을 먼저 업로드해 주세요.');
        }

        $isPg = $request->input('payment_method') !== 'bank';
        $data = $request->only(['payment_method', 'bank', 'depositor']);

        $order = app(\App\Services\OrderPlacement::class)->placeSplit($user, $shipments, $data, $isPg);

        $request->session()->forget('split_shipments');

        if ($isPg) {
            return redirect()->route('order.pay', $order);
        }

        return redirect()->route('order.complete', $order)->with('ok', '분할배송 주문이 접수되었습니다.');
    }

    public function complete(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items');

        return view('order.complete', compact('order'));
    }
}
