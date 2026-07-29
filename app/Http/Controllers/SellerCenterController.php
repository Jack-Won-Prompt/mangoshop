<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * 판매자(수입사) 콘솔 — 입점 수입사 전용. 관리자 콘솔과 유사하되 판매자 범위로 한정.
 * (회원관리·정산정책·사이트설정 등 관리자 전용 기능은 제외)
 */
class SellerCenterController extends Controller
{
    private function seller(Request $request)
    {
        $seller = $request->user()?->seller;
        abort_unless($seller, 403, '입점 수입사 전용 페이지입니다.');

        return $seller;
    }

    public function index(Request $request)
    {
        $seller = $this->seller($request);

        $stats = [
            'products'   => $seller->products()->count(),
            'onsale'     => $seller->products()->where('is_active', true)->where('sale_status', 'on_sale')->count(),
            'orders'     => $seller->orders()->count(),
            'settlement' => (int) $seller->settlements()->where('status', '!=', 'settled')->sum('net_amount'),
        ];
        $products = $seller->products()->latest()->take(6)->get();
        $orders   = $seller->orders()->latest()->take(6)->get();

        return view('seller-center.index', compact('seller', 'stats', 'products', 'orders'));
    }

    public function products(Request $request)
    {
        $seller = $this->seller($request);
        $q = $seller->products()->latest();
        if ($request->filled('q')) {
            $q->where('name', 'like', '%'.$request->string('q').'%');
        }
        $products = $q->paginate(20)->withQueryString();

        return view('seller-center.products', compact('seller', 'products'));
    }

    /** 상품 재고·가격·판매상태 수정(자사 상품만) */
    public function updateProduct(Request $request, Product $product)
    {
        $seller = $this->seller($request);
        abort_unless($product->seller_id === $seller->id, 403);

        $data = $request->validate([
            'price'           => ['required', 'integer', 'min:0'],
            'wholesale_price' => ['nullable', 'integer', 'min:0'],
            'stock'           => ['required', 'integer', 'min:0'],
            'sale_status'     => ['required', 'in:on_sale,soldout,closed,inbound'],
        ]);
        $product->update($data);

        return back()->with('ok', '상품 정보가 수정되었습니다.');
    }

    public function orders(Request $request)
    {
        $seller = $this->seller($request);
        $orders = $seller->orders()->with('items')->latest()->paginate(20);

        return view('seller-center.orders', compact('seller', 'orders'));
    }

    public function settlements(Request $request)
    {
        $seller = $this->seller($request);
        $settlements = $seller->settlements()->with('order')->latest()->paginate(20);
        $summary = [
            'pending' => (int) $seller->settlements()->where('status', '!=', 'settled')->sum('net_amount'),
            'settled' => (int) $seller->settlements()->where('status', 'settled')->sum('net_amount'),
        ];

        return view('seller-center.settlements', compact('seller', 'settlements', 'summary'));
    }

    public function store(Request $request)
    {
        $seller = $this->seller($request);

        return view('seller-center.store', compact('seller'));
    }

    public function storeUpdate(Request $request)
    {
        $seller = $this->seller($request);
        $data = $request->validate([
            'ceo_name'                => ['nullable', 'string', 'max:50'],
            'phone'                   => ['nullable', 'string', 'max:30'],
            'email'                   => ['nullable', 'email', 'max:100'],
            'origin_focus'            => ['nullable', 'string', 'max:50'],
            'intro'                   => ['nullable', 'string', 'max:300'],
            'shipping_fee'            => ['nullable', 'integer', 'min:0'],
            'free_shipping_threshold' => ['nullable', 'integer', 'min:0'],
            'shipping_notice'         => ['nullable', 'string', 'max:300'],
        ]);
        $seller->update($data);

        return back()->with('ok', '스토어 정보가 저장되었습니다.');
    }
}
