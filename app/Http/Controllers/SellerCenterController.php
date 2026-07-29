<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    /** 상품 등록 폼 */
    public function createProduct(Request $request)
    {
        $seller = $this->seller($request);

        return view('seller-center.product-form', [
            'seller' => $seller, 'product' => new Product(['sale_status' => 'on_sale', 'moq' => 1, 'is_active' => true]),
            'categories' => $this->categories(),
        ]);
    }

    public function storeProduct(Request $request)
    {
        $seller = $this->seller($request);
        [$data, $image] = $this->validated($request);

        $data['seller_id']   = $seller->id;
        $data['slug']        = $this->uniqueSlug($data['name']);
        $data['code']        = 'S'.$seller->id.'-'.strtoupper(Str::random(5));
        $data['unit']        = 'BOX';
        if ($image) {
            $data['thumbnail'] = $image;
        }

        Product::create($data);

        return redirect()->route('seller.center.products')->with('ok', '상품이 등록되었습니다.');
    }

    /** 상품 수정 폼 */
    public function editProduct(Request $request, Product $product)
    {
        $seller = $this->seller($request);
        abort_unless($product->seller_id === $seller->id, 403);

        return view('seller-center.product-form', [
            'seller' => $seller, 'product' => $product, 'categories' => $this->categories(),
        ]);
    }

    public function updateProduct(Request $request, Product $product)
    {
        $seller = $this->seller($request);
        abort_unless($product->seller_id === $seller->id, 403);

        [$data, $image] = $this->validated($request);
        if ($image) {
            $data['thumbnail'] = $image;
        }
        $product->update($data);

        return redirect()->route('seller.center.products')->with('ok', '상품이 수정되었습니다.');
    }

    public function destroyProduct(Request $request, Product $product)
    {
        $seller = $this->seller($request);
        abort_unless($product->seller_id === $seller->id, 403);
        $product->delete();

        return back()->with('ok', '상품이 삭제되었습니다.');
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

    /* ===== 헬퍼 ===== */

    private function categories()
    {
        return Category::where('is_active', true)->orderBy('sort_order')->with('children')->get();
    }

    /** 상품 폼 검증 → [저장데이터, 업로드이미지경로|null] */
    private function validated(Request $request): array
    {
        $v = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'category_id'     => ['required', 'exists:categories,id'],
            'origin'          => ['nullable', 'string', 'max:50'],
            'variety'         => ['nullable', 'string', 'max:50'],
            'grade'           => ['nullable', 'string', 'max:20'],
            'box_spec'        => ['nullable', 'string', 'max:50'],
            'weight_kg'       => ['nullable', 'numeric', 'min:0'],
            'price'           => ['required', 'integer', 'min:0'],
            'wholesale_price' => ['nullable', 'integer', 'min:0'],
            'moq'             => ['nullable', 'integer', 'min:1'],
            'stock'           => ['required', 'integer', 'min:0'],
            'sale_status'     => ['required', 'in:on_sale,soldout,closed,inbound'],
            'summary'         => ['nullable', 'string', 'max:200'],
            'description'     => ['nullable', 'string', 'max:4000'],
            'image'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $image = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $dir = public_path('product/uploads');
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $filename = 'sp'.now()->format('YmdHis').Str::lower(Str::random(5)).'.'.$file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $image = 'product/uploads/'.$filename;
        }

        $data = collect($v)->except('image')->toArray();
        $data['member_price'] = $data['wholesale_price'] ?? null;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['moq'] = $data['moq'] ?? 1;

        return [$data, $image];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'item-'.Str::lower(Str::random(6));
        }
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
