<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\RichTextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 관리자 상품 등록/수정 — korsafety 동일 기능(리치에디터·갤러리/상세이미지·옵션·메인이미지 에디터).
 */
class ProductController extends Controller
{
    /* ===== 목록 ===== */
    public function index(Request $request)
    {
        $q = Product::with('category')->latest();

        if ($request->filled('q')) {
            $kw = trim((string) $request->string('q'));
            $q->where(fn ($w) => $w->where('name', 'like', "%{$kw}%")->orWhere('code', 'like', "%{$kw}%")->orWhere('maker', 'like', "%{$kw}%"));
        }
        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }
        if ($request->get('state') === 'onsale') {
            $q->where('is_active', true)->where('sale_status', 'on_sale');
        } elseif ($request->get('state') === 'hidden') {
            $q->where('is_active', false);
        } elseif ($request->get('state') === 'soldout') {
            $q->where('sale_status', 'soldout');
        }

        $products = $q->paginate(20)->withQueryString();
        $categories = $this->categories();
        $stats = [
            'total'  => Product::count(),
            'onsale' => Product::where('is_active', true)->where('sale_status', 'on_sale')->count(),
            'hidden' => Product::where('is_active', false)->count(),
        ];

        return view('admin.products.index', compact('products', 'categories', 'stats'));
    }

    public function create()
    {
        $product = new Product(['sale_status' => 'on_sale', 'tax_type' => 'exempt', 'unit' => 'BOX', 'moq' => 1, 'is_active' => true, 'stock' => 0]);

        return view('admin.products.form', $this->formData($product));
    }

    public function store(Request $request)
    {
        $this->extractInlineImages($request); // 붙여넣기 base64 → 파일화(검증 전)
        $data = $this->validated($request);
        $product = new Product();
        $this->fill($product, $data, $request);
        $product->save();

        $this->handleImages($product, $request);
        $this->syncOptions($product, $request);

        return redirect()->route('admin.products.index')->with('ok', '상품이 등록되었습니다.');
    }

    public function edit(Product $product)
    {
        $product->load('galleryImages', 'detailImages', 'options');

        return view('admin.products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product)
    {
        $this->extractInlineImages($request); // 붙여넣기 base64 → 파일화(검증 전)
        $data = $this->validated($request);
        $this->fill($product, $data, $request);
        $product->save();

        $removeIds = array_filter((array) $request->input('remove_images', []));
        if ($removeIds) {
            ProductImage::where('product_id', $product->id)->whereIn('id', $removeIds)->delete();
        }
        $this->handleImages($product, $request);
        $this->syncOptions($product, $request);

        return redirect()->route('admin.products.edit', $product)->with('ok', '상품이 수정되었습니다.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('ok', '상품이 삭제되었습니다.');
    }

    /** 리스트에서 가격문의 on/off 빠른 토글 */
    public function toggleQuote(Product $product)
    {
        $product->update(['is_quote' => ! $product->is_quote]);

        return back()->with('ok', $product->name.' → 가격문의 '.($product->is_quote ? '설정' : '해제').'되었습니다.');
    }

    /** 대표이미지 편집 화면(회전·밝기·대비·자르기) */
    public function editImage(Product $product)
    {
        abort_unless($product->thumbnail, 404, '대표 이미지가 없습니다.');

        return view('admin.products.image-editor', ['product' => $product]);
    }

    /** 편집본(base64 dataURL) 저장 → 대표이미지 갱신 */
    public function saveImage(Request $request, Product $product)
    {
        $data = (string) $request->input('image', '');
        if (! preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $data, $m)) {
            return back()->withErrors(['image' => '이미지 데이터가 올바르지 않습니다.']);
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $bin = base64_decode(substr($data, strpos($data, ',') + 1), true);
        if ($bin === false || strlen($bin) < 100) {
            return back()->withErrors(['image' => '이미지 저장에 실패했습니다.']);
        }
        $dir = public_path('product/uploads');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = 'edit_'.now()->format('Ymd_His').'_'.Str::lower(Str::random(5)).'.'.$ext;
        file_put_contents($dir.'/'.$name, $bin);

        $product->update(['thumbnail' => 'product/uploads/'.$name]);

        return redirect()->route('admin.products.edit', $product)->with('ok', '대표 이미지가 편집·저장되었습니다.');
    }

    /** 리치에디터 이미지 업로드(AJAX) → {url} */
    public function editorUpload(Request $request)
    {
        // 클립보드 붙여넣기 파일은 확장자가 없을 수 있어 mimetypes(실제 MIME)로 검증
        $request->validate([
            'file' => ['required', 'image', 'mimetypes:image/jpeg,image/png,image/webp,image/gif', 'max:51200'],
        ], [
            'file.max'       => '이미지는 50MB 이하만 업로드할 수 있습니다.',
            'file.mimetypes' => 'jpg, png, webp, gif 이미지만 붙여넣을 수 있습니다.',
            'file.image'     => '이미지 파일만 붙여넣을 수 있습니다.',
        ]);
        $path = $this->saveUpload($request->file('file'), 'editor');

        return response()->json(['url' => asset(ltrim($path, '/'))]);
    }

    /* ===== 검증 ===== */
    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id'     => ['required', 'exists:categories,id'],
            'brand_id'        => ['nullable', 'exists:brands,id'],
            'name'            => ['required', 'string', 'max:250'],
            'slug'            => ['nullable', 'string', 'max:180'],
            'code'            => ['nullable', 'string', 'max:64'],
            'unit'            => ['nullable', 'string', 'max:20'],
            'maker'           => ['nullable', 'string', 'max:120'],
            'price'           => ['required', 'integer', 'min:0'],
            'cost'            => ['nullable', 'integer', 'min:0'],
            'member_price'    => ['nullable', 'integer', 'min:0'],
            'wholesale_price' => ['nullable', 'integer', 'min:0'],
            'tax_type'        => ['required', 'in:taxable,exempt'],
            'stock'           => ['required', 'integer', 'min:0'],
            'moq'             => ['nullable', 'integer', 'min:1'],
            'origin'          => ['nullable', 'string', 'max:50'],
            'variety'         => ['nullable', 'string', 'max:50'],
            'grade'           => ['nullable', 'string', 'max:30'],
            'box_spec'        => ['nullable', 'string', 'max:80'],
            'weight_kg'       => ['nullable', 'numeric', 'min:0'],
            'sale_status'     => ['required', 'in:on_sale,soldout,closed,inbound'],
            'summary'         => ['nullable', 'string', 'max:300'],
            'description'     => ['nullable', 'string', 'max:500000'],
            'badge'           => ['nullable', 'string', 'max:30'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
            'thumbnail'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'gallery.*'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'detail_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'options'                 => ['nullable', 'array', 'max:100'],
            'options.*.name'          => ['nullable', 'string', 'max:120'],
            'options.*.group_name'    => ['nullable', 'string', 'max:60'],
            'options.*.extra_price'   => ['nullable', 'integer', 'min:-10000000', 'max:10000000'],
            'options.*.stock'         => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
    }

    /** 필드 매핑 + 슬러그 생성 */
    private function fill(Product $product, array $data, Request $request): void
    {
        foreach (['category_id', 'brand_id', 'name', 'code', 'unit', 'maker', 'price', 'cost', 'member_price',
            'wholesale_price', 'tax_type', 'stock', 'moq', 'origin', 'variety', 'grade', 'box_spec', 'weight_kg',
            'sale_status', 'summary', 'badge', 'sort_order'] as $f) {
            $product->{$f} = $data[$f] ?? null;
        }
        $product->unit        = trim((string) ($data['unit'] ?? '')) ?: 'BOX'; // NOT NULL 보정
        $product->sort_order  = (int) ($data['sort_order'] ?? 0);              // NOT NULL 보정
        $product->stock       = (int) ($data['stock'] ?? 0);
        $product->description = RichTextSanitizer::clean($data['description'] ?? null);
        $product->is_active   = $request->boolean('is_active');
        $product->is_featured = $request->boolean('is_featured');
        $product->is_best     = $request->boolean('is_best');
        $product->is_new      = $request->boolean('is_new');
        $product->is_quote    = $request->boolean('is_quote');
        $product->moq         = (int) ($data['moq'] ?? 1) ?: 1;

        // 슬러그: 비어있을 때만 생성(수정 시 재생성 안 함)
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '' && ! $product->slug) {
            $slug = Str::slug($data['name']) ?: 'p'.Str::lower(Str::random(6));
        }
        if ($slug !== '' && $slug !== $product->slug) {
            $product->slug = $this->uniqueSlug($slug, $product->id);
        }

        if ($request->hasFile('thumbnail')) {
            $product->thumbnail = ltrim($this->saveUpload($request->file('thumbnail')), '/');
        }
    }

    /** 갤러리/상세 이미지 저장 + 메인 없으면 첫 갤러리로 보완 */
    private function handleImages(Product $product, Request $request): void
    {
        $this->storeImages($product, $request, 'gallery', 'gallery');
        $this->storeImages($product, $request, 'detail_images', 'detail');

        if (! $product->thumbnail && $product->galleryImages()->exists()) {
            $product->update(['thumbnail' => ltrim($product->galleryImages()->first()->path, '/')]);
        }
    }

    private function storeImages(Product $product, Request $request, string $field, string $type): void
    {
        if (! $request->hasFile($field)) {
            return;
        }
        $sort = (int) $product->productImages()->where('type', $type)->max('sort');
        foreach ($request->file($field) as $file) {
            if (! $file) {
                continue;
            }
            ProductImage::create([
                'product_id' => $product->id,
                'path'       => ltrim($this->saveUpload($file), '/'),
                'type'       => $type,
                'sort'       => ++$sort,
            ]);
        }
    }

    /** 옵션 upsert + 미포함 삭제 */
    private function syncOptions(Product $product, Request $request): void
    {
        $rows = (array) $request->input('options', []);
        $keepIds = [];
        $sort = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $attrs = [
                'group_name'  => trim((string) ($row['group_name'] ?? '')) ?: null,
                'name'        => mb_substr($name, 0, 120),
                'extra_price' => (int) ($row['extra_price'] ?? 0),
                'stock'       => max(0, (int) ($row['stock'] ?? 0)),
                'is_active'   => ! empty($row['is_active']),
                'sort'        => $sort++,
            ];
            $id = (int) ($row['id'] ?? 0);
            $option = $id ? $product->options()->find($id) : null;
            $option ? $option->update($attrs) : $option = $product->options()->create($attrs);
            $keepIds[] = $option->id;
        }
        $product->options()->whereNotIn('id', $keepIds ?: [0])->delete();
    }

    private function saveUpload($file, ?string $sub = null): string
    {
        $dir = public_path('product/uploads'.($sub ? '/'.$sub : ''));
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // 확장자: 원본 → 없으면 MIME 추론(클립보드 blob 대응)
        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'png'));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        $name = now()->format('Ymd_His').'_'.Str::lower(Str::random(6)).'.'.$ext;
        $file->move($dir, $name);

        return '/product/uploads'.($sub ? '/'.$sub : '').'/'.$name;
    }

    /**
     * 상세설명에 붙여넣은 이미지를 서버에 자체 저장한다(검증 이전 실행).
     *  1) 인라인 base64(data:image/...) → 파일화
     *  2) 외부 http(s) 이미지(<img src>) → 다운로드하여 자체 저장(자사 호스트는 유지)
     * → 붙여넣기 출처(스크린샷/파일/외부 웹/문서)에 관계없이 상세이미지가 남는다.
     */
    private function extractInlineImages(Request $request): void
    {
        $html = (string) $request->input('description', '');
        if ($html === '') {
            return;
        }

        $dir = public_path('product/uploads/editor');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $extMap = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $changed = false;

        // 1) 인라인 base64
        if (stripos($html, 'data:image/') !== false) {
            $b64 = ['png' => 'png', 'jpeg' => 'jpg', 'jpg' => 'jpg', 'gif' => 'gif', 'webp' => 'webp'];
            $html = preg_replace_callback(
                '#data:image/(png|jpe?g|gif|webp);base64,([A-Za-z0-9+/=\s]+)#i',
                function ($m) use ($dir, $b64, &$changed) {
                    $bin = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
                    if ($bin === false || $bin === '') {
                        return $m[0];
                    }
                    $name = now()->format('Ymd_His').'_'.Str::lower(Str::random(8)).'.'.($b64[strtolower($m[1])] ?? 'png');
                    if (@file_put_contents($dir.'/'.$name, $bin) === false) {
                        return $m[0];
                    }
                    $changed = true;

                    return asset('product/uploads/editor/'.$name);
                },
                $html
            );
        }

        // 2) 외부 http(s) 이미지 다운로드(자사 호스트 제외)
        if (stripos($html, 'src="http') !== false) {
            $ownHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $html = preg_replace_callback(
                '#(<img\b[^>]*\bsrc=")(https?://[^"]+)(")#i',
                function ($m) use ($dir, $extMap, $ownHost, &$changed) {
                    $url = $m[2];
                    $host = parse_url($url, PHP_URL_HOST);
                    if ($ownHost && $host && stripos($host, $ownHost) !== false) {
                        return $m[0]; // 자사 이미지 유지
                    }
                    try {
                        $resp = \Illuminate\Support\Facades\Http::timeout(8)->retry(1, 200)->get($url);
                        if (! $resp->ok()) {
                            return $m[0];
                        }
                        $ct = strtolower(trim(explode(';', (string) $resp->header('Content-Type'))[0]));
                        $bin = $resp->body();
                        if (! isset($extMap[$ct]) || strlen($bin) < 50 || strlen($bin) > 50 * 1024 * 1024) {
                            return $m[0];
                        }
                        $name = now()->format('Ymd_His').'_'.Str::lower(Str::random(8)).'.'.$extMap[$ct];
                        if (@file_put_contents($dir.'/'.$name, $bin) === false) {
                            return $m[0];
                        }
                        $changed = true;

                        return $m[1].asset('product/uploads/editor/'.$name).$m[3];
                    } catch (\Throwable $e) {
                        return $m[0];
                    }
                },
                $html
            );
        }

        if ($changed) {
            $request->merge(['description' => $html]);
        }
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = Str::limit($base, 170, '');
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function categories()
    {
        return Category::orderBy('sort_order')->orderBy('name')->get();
    }

    private function formData(Product $product): array
    {
        return [
            'product'    => $product,
            'categories' => $this->categories(),
            'brands'     => Brand::orderBy('sort_order')->orderBy('name')->get(),
        ];
    }
}
