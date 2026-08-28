<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = $user->cartItems()->with(['product.brand', 'option'])->get()
            ->filter(fn ($i) => $i->product !== null);

        $summary = $this->summarize($items, $user);

        return view('cart.index', compact('items', 'summary'));
    }

    public function add(Request $request, Product $product)
    {
        $qty = max(1, (int) $request->input('quantity', 1));
        $user = $request->user();

        // 가격문의 상품은 장바구니 담기 불가(견적문의로 안내)
        if ($product->is_quote) {
            return back()->with('error', '가격문의 상품입니다. 견적문의로 문의해 주세요.');
        }

        // 옵션 처리 — 옵션이 있는 상품은 반드시 선택
        $optionId = (int) $request->input('option_id', 0) ?: null;
        if ($product->activeOptions()->exists()) {
            $option = $optionId ? $product->activeOptions()->find($optionId) : null;
            if (! $option) {
                return back()->with('error', '옵션을 선택해 주세요.');
            }
            $optionId = $option->id;
        } else {
            $optionId = null; // 옵션 없는 상품은 무시
        }

        // 같은 상품이라도 옵션이 다르면 별도 라인
        $item = CartItem::firstOrNew(['user_id' => $user->id, 'product_id' => $product->id, 'option_id' => $optionId]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $qty;
        $item->save();

        if ($request->boolean('buy_now')) {
            return redirect()->route('order.checkout');
        }

        return back()->with('ok', '장바구니에 담았습니다.');
    }

    public function update(Request $request, CartItem $item)
    {
        abort_unless($item->user_id === $request->user()->id, 403);
        $item->update(['quantity' => max(1, (int) $request->input('quantity', 1))]);

        return back()->with('ok', '수량이 변경되었습니다.');
    }

    public function remove(Request $request, CartItem $item)
    {
        abort_unless($item->user_id === $request->user()->id, 403);
        $item->delete();

        return back()->with('ok', '상품을 삭제했습니다.');
    }

    /** 장바구니 합계 계산 (회원유형별 단가 적용) */
    public static function summarize($items, $user): array
    {
        $subtotal = 0;
        foreach ($items as $i) {
            $unit = $i->product->priceFor($user) + (int) ($i->option?->extra_price ?? 0);
            $subtotal += max(0, $unit) * $i->quantity;
        }
        // 배송비 별도 — 콜드체인·수입사/지역별로 상이하여 주문 후 별도 정산(결제금액 미포함)
        $shipping = 0;

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total'    => $subtotal + $shipping,
            'count'    => $items->count(),
        ];
    }
}
