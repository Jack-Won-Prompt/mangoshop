<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\Request;

/**
 * 수입사(판매자) 공개 스토어 페이지 — /store/{slug}
 */
class SellerStoreController extends Controller
{
    public function index()
    {
        $sellers = Seller::approved()->withCount('products')->orderBy('sort_order')->get();

        return view('seller.index', compact('sellers'));
    }

    public function show(Request $request, string $slug)
    {
        $seller = Seller::approved()->where('slug', $slug)->firstOrFail();

        $products = Product::active()->where('seller_id', $seller->id)
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->orderByDesc('is_best')->latest('sales_count')
            ->paginate(20)->withQueryString();

        return view('seller.show', compact('seller', 'products'));
    }
}
