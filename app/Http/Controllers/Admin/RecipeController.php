<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeImage;
use App\Support\RichTextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 관리자 레시피(공식) 등록/수정 — 리치에디터(글+사진)·갤러리·상단고정·카테고리·SEO.
 * 사용자 글도 이 컨트롤러의 목록에서 숨김/삭제/고정 관리(Phase 2).
 */
class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $q = Recipe::with('category', 'user')->latest();
        if ($request->filled('q')) {
            $kw = trim((string) $request->string('q'));
            $q->where('title', 'like', "%{$kw}%");
        }
        if ($request->filled('category')) {
            $q->where('recipe_category_id', $request->integer('category'));
        }
        if (in_array($request->get('kind'), ['official', 'user'], true)) {
            $request->get('kind') === 'official' ? $q->whereNull('user_id') : $q->whereNotNull('user_id');
        }

        $recipes = $q->paginate(20)->withQueryString();
        $categories = RecipeCategory::orderBy('sort_order')->get();

        return view('admin.recipes.index', compact('recipes', 'categories'));
    }

    public function create()
    {
        $recipe = new Recipe(['status' => 'published', 'is_official' => true, 'is_pinned' => true]);

        return view('admin.recipes.form', [
            'recipe' => $recipe,
            'categories' => RecipeCategory::orderBy('sort_order')->get(),
            'allProducts' => $this->productOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->extractInlineImages($request);
        $data = $this->validated($request);
        $recipe = new Recipe();
        $this->fill($recipe, $data, $request);
        $recipe->save();
        $this->handleImages($recipe, $request);
        $this->syncProducts($recipe, $request);

        return redirect()->route('admin.recipes.index')->with('ok', '레시피가 등록되었습니다.');
    }

    public function edit(Recipe $recipe)
    {
        $recipe->load('images', 'products');

        return view('admin.recipes.form', [
            'recipe' => $recipe,
            'categories' => RecipeCategory::orderBy('sort_order')->get(),
            'allProducts' => $this->productOptions(),
        ]);
    }

    /** 상품 연결 선택지 (활성 상품) */
    private function productOptions()
    {
        return \App\Models\Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function update(Request $request, Recipe $recipe)
    {
        $this->extractInlineImages($request);
        $data = $this->validated($request);
        $this->fill($recipe, $data, $request);
        $recipe->save();

        $removeIds = array_filter((array) $request->input('remove_images', []));
        if ($removeIds) {
            RecipeImage::where('recipe_id', $recipe->id)->whereIn('id', $removeIds)->delete();
        }
        $this->handleImages($recipe, $request);
        $this->syncProducts($recipe, $request);

        return redirect()->route('admin.recipes.edit', $recipe)->with('ok', '레시피가 수정되었습니다.');
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();

        return redirect()->route('admin.recipes.index')->with('ok', '레시피가 삭제되었습니다.');
    }

    /** 상단 고정 on/off */
    public function togglePin(Recipe $recipe)
    {
        $recipe->update(['is_pinned' => ! $recipe->is_pinned]);

        return back()->with('ok', $recipe->title.' → 고정 '.($recipe->is_pinned ? '설정' : '해제'));
    }

    /** 상태(공개/숨김) 토글 — 사용자 글 모더레이션 */
    public function toggleStatus(Recipe $recipe)
    {
        $recipe->update(['status' => $recipe->status === 'published' ? 'hidden' : 'published']);

        return back()->with('ok', $recipe->title.' → '.($recipe->status === 'published' ? '공개' : '숨김'));
    }

    public function editorUpload(Request $request)
    {
        $request->validate(['file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192']]);
        $path = $this->saveUpload($request->file('file'));

        return response()->json(['url' => asset(ltrim($path, '/'))]);
    }

    /* ===== 내부 ===== */
    private function validated(Request $request): array
    {
        return $request->validate([
            'recipe_category_id' => ['required', 'exists:recipe_categories,id'],
            'title'            => ['required', 'string', 'max:200'],
            'slug'             => ['nullable', 'string', 'max:200'],
            'summary'          => ['nullable', 'string', 'max:300'],
            'body'             => ['nullable', 'string', 'max:500000'],
            'cook_time'        => ['nullable', 'string', 'max:50'],
            'ingredients'      => ['nullable', 'string', 'max:3000'],
            'tags'             => ['nullable', 'string', 'max:200'],
            'meta_title'       => ['nullable', 'string', 'max:150'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'video_url'        => ['nullable', 'url', 'max:300'],
            'video'            => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:102400'], // 100MB
            'cover'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'gallery.*'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'products'         => ['nullable', 'array'],
            'products.*'       => ['integer', 'exists:products,id'],
        ]);
    }

    /** 연결 상품 동기화 */
    private function syncProducts(Recipe $recipe, Request $request): void
    {
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('products', []))));
        $sync = [];
        foreach ($ids as $i => $id) {
            $sync[$id] = ['sort' => $i];
        }
        $recipe->products()->sync($sync);
    }

    private function fill(Recipe $recipe, array $data, Request $request): void
    {
        foreach (['recipe_category_id', 'title', 'summary', 'cook_time', 'ingredients', 'meta_title', 'meta_description'] as $f) {
            $recipe->{$f} = $data[$f] ?? null;
        }
        $recipe->tags = Recipe::normalizeTags($data['tags'] ?? null);
        $recipe->body       = RichTextSanitizer::clean($data['body'] ?? null);
        $recipe->is_official = true;                          // 관리자 등록 = 공식
        $recipe->is_pinned  = $request->boolean('is_pinned');
        $recipe->status     = $request->boolean('hidden') ? 'hidden' : 'published';
        $recipe->published_at = $recipe->published_at ?: now();

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Recipe::uniqueSlug($data['title'], (int) $data['recipe_category_id'], $recipe->id);
        } else {
            $slug = RecipeCategory::slugify($slug);
        }
        $recipe->slug = $slug;

        if ($request->hasFile('cover')) {
            $recipe->cover_image = $this->saveUpload($request->file('cover'));
        }

        // 유튜브/외부 영상 링크
        $recipe->video_url = trim((string) ($data['video_url'] ?? '')) ?: null;
        // 영상 파일 업로드(선택) — 새로 올리면 교체
        if ($request->hasFile('video')) {
            $recipe->video_path = $this->saveUpload($request->file('video'));
        } elseif ($request->boolean('remove_video')) {
            $recipe->video_path = null;
        }
    }

    private function handleImages(Recipe $recipe, Request $request): void
    {
        if (! $request->hasFile('gallery')) {
            return;
        }
        $sort = (int) $recipe->images()->max('sort');
        foreach ($request->file('gallery') as $file) {
            $recipe->images()->create(['path' => $this->saveUpload($file), 'sort' => ++$sort]);
        }
    }

    private function saveUpload($file): string
    {
        $dir = public_path('recipe/uploads');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = now()->format('Ymd_His').'_'.Str::lower(Str::random(6)).'.'.strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);

        return '/recipe/uploads/'.$name;
    }

    /** 본문 인라인 base64 이미지를 파일로 저장(검증 max 초과 방지) */
    private function extractInlineImages(Request $request): void
    {
        $html = (string) $request->input('body', '');
        if (stripos($html, 'data:image/') === false) {
            return;
        }
        $ext = ['png' => 'png', 'jpeg' => 'jpg', 'jpg' => 'jpg', 'gif' => 'gif', 'webp' => 'webp'];
        $dir = public_path('recipe/uploads');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $html = preg_replace_callback(
            '#data:image/(png|jpe?g|gif|webp);base64,([A-Za-z0-9+/=\s]+)#i',
            function ($m) use ($ext, $dir) {
                $bin = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
                if ($bin === false || $bin === '') {
                    return $m[0];
                }
                $name = now()->format('Ymd_His').'_'.Str::lower(Str::random(8)).'.'.($ext[strtolower($m[1])] ?? 'png');
                if (@file_put_contents($dir.'/'.$name, $bin) === false) {
                    return $m[0];
                }

                return asset('recipe/uploads/'.$name);
            },
            $html
        );
        $request->merge(['body' => $html]);
    }
}
