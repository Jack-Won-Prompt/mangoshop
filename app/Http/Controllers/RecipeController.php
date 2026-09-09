<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeCategory;
use Illuminate\Http\Request;

/**
 * 레시피 커뮤니티(공개) — 목록/카테고리/상세.
 * 카테고리 페이지: 공식·고정 레시피를 항상 최상단에 노출 후 최신 글.
 */
class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $categories = RecipeCategory::active()->orderBy('sort_order')->withCount(['recipes' => fn ($q) => $q->published()])->get();

        $pinned = Recipe::published()->with('category')
            ->where('is_pinned', true)->latest('published_at')->take(6)->get();

        $latest = Recipe::published()->with('category', 'user')
            ->latest('published_at')->take(12)->get();

        $popular = Recipe::published()->with('category')
            ->popular()->take(8)->get();

        return view('community.recipes.index', compact('categories', 'pinned', 'latest', 'popular'));
    }

    public function category(RecipeCategory $category, Request $request)
    {
        abort_unless($category->is_active, 404);

        // 공식·고정 레시피(상단 고정)
        $pinned = $category->recipes()->published()
            ->where(fn ($q) => $q->where('is_pinned', true)->orWhere('is_official', true))
            ->orderByDesc('is_pinned')->latest('published_at')->get();

        // 사용자/일반 글(고정 제외) — 최신 or 인기
        $sort = $request->get('sort') === 'popular' ? 'popular' : 'latest';
        $posts = $category->recipes()->published()
            ->where('is_pinned', false)->where('is_official', false)
            ->with('user')
            ->when($sort === 'popular', fn ($q) => $q->popular(), fn ($q) => $q->latest('published_at'))
            ->paginate(12)->withQueryString();

        $categories = RecipeCategory::active()->orderBy('sort_order')->get();

        return view('community.recipes.category', compact('category', 'pinned', 'posts', 'categories', 'sort'));
    }

    public function show(RecipeCategory $category, string $recipe)
    {
        abort_unless($category->is_active, 404);

        $item = Recipe::published()->with('category', 'user', 'images')
            ->where('recipe_category_id', $category->id)
            ->where('slug', $recipe)
            ->firstOrFail();

        // 조회수(세션 중복 방지)
        $key = 'recipe_viewed_'.$item->id;
        if (! session()->has($key)) {
            $item->increment('view_count');
            session()->put($key, true);
        }

        $related = Recipe::published()->with('category')
            ->where('recipe_category_id', $category->id)
            ->where('id', '!=', $item->id)
            ->latest('published_at')->take(4)->get();

        return view('community.recipes.show', ['recipe' => $item, 'category' => $category, 'related' => $related]);
    }

    /* ===== 회원 레시피 글쓰기(Phase 2) ===== */

    public function create()
    {
        return view('community.recipes.write', [
            'recipe' => new Recipe(),
            'categories' => RecipeCategory::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (filled($request->input('website'))) {              // 허니팟
            return redirect()->route('community.recipes');
        }
        $key = 'recipe_write:'.$request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 8)) {
            return back()->withInput()->with('error', '글이 너무 자주 등록되었습니다. 잠시 후 다시 시도해 주세요.');
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);

        $data = $this->validateWrite($request);
        $recipe = new Recipe();
        $recipe->user_id = $request->user()->id;
        $recipe->is_official = false;
        $recipe->is_pinned = false;
        $recipe->status = 'published';                          // 즉시 게시
        $recipe->published_at = now();
        $this->fillWrite($recipe, $data, $request);
        $recipe->save();
        $this->handleGallery($recipe, $request);

        // 관리자 알림(모더레이션 참고)
        \App\Support\AdminPush::toAdmins('📝 새 회원 레시피', $request->user()->name.' · '.\Illuminate\Support\Str::limit($recipe->title, 40),
            ['type' => 'recipe_post', 'recipe_id' => (string) $recipe->id]);

        return redirect($recipe->url)->with('ok', '레시피가 등록되었습니다.');
    }

    public function editOwn(Request $request, Recipe $recipe)
    {
        $this->authorizeOwner($request, $recipe);

        return view('community.recipes.write', [
            'recipe' => $recipe->load('images'),
            'categories' => RecipeCategory::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function updateOwn(Request $request, Recipe $recipe)
    {
        $this->authorizeOwner($request, $recipe);
        $data = $this->validateWrite($request);
        $this->fillWrite($recipe, $data, $request);
        $recipe->save();

        $removeIds = array_filter((array) $request->input('remove_images', []));
        if ($removeIds) {
            $recipe->images()->whereIn('id', $removeIds)->delete();
        }
        $this->handleGallery($recipe, $request);

        return redirect($recipe->url)->with('ok', '레시피가 수정되었습니다.');
    }

    public function destroyOwn(Request $request, Recipe $recipe)
    {
        $this->authorizeOwner($request, $recipe);
        $cat = $recipe->category->slug ?? null;
        $recipe->delete();

        return redirect($cat ? route('community.recipe.category', $cat) : route('community.recipes'))
            ->with('ok', '레시피가 삭제되었습니다.');
    }

    /** 신고 — 관리자 알림(즉시게시 + 신고/숨김 정책) */
    public function report(Request $request, Recipe $recipe)
    {
        \App\Support\AdminPush::toAdmins('🚨 레시피 신고 접수', \Illuminate\Support\Str::limit($recipe->title, 40).' (신고자: '.$request->user()->name.')',
            ['type' => 'recipe_report', 'recipe_id' => (string) $recipe->id]);

        return back()->with('ok', '신고가 접수되었습니다. 관리자가 확인합니다.');
    }

    /* ===== 좋아요 / 댓글 / 태그 (Phase 3) ===== */

    public function like(Request $request, Recipe $recipe)
    {
        $user = $request->user();
        $existing = $recipe->likers()->where('user_id', $user->id)->exists();
        if ($existing) {
            $recipe->likers()->detach($user->id);
            $recipe->decrement('like_count');
            $liked = false;
        } else {
            $recipe->likers()->attach($user->id);
            $recipe->increment('like_count');
            $liked = true;
        }
        $recipe->refresh();

        if ($request->expectsJson()) {
            return response()->json(['liked' => $liked, 'count' => $recipe->like_count]);
        }

        return back();
    }

    public function comment(Request $request, Recipe $recipe)
    {
        abort_unless($recipe->status === 'published', 404);
        if (filled($request->input('website'))) {
            return redirect($recipe->url);
        }
        $key = 'recipe_c:'.$request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 20)) {
            return back()->with('error', '댓글이 너무 자주 등록되었습니다. 잠시 후 다시 시도해 주세요.');
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);

        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $isAdmin = (bool) $request->user()->is_admin;
        \App\Models\RecipeComment::create([
            'recipe_id' => $recipe->id,
            'user_id'   => $request->user()->id,
            'body'      => $data['body'],
            'is_admin'  => $isAdmin,
            'status'    => 'published',
        ]);
        $recipe->increment('comment_count');

        if (! $isAdmin) {
            \App\Support\AdminPush::toAdmins('💬 레시피 새 댓글', \Illuminate\Support\Str::limit($recipe->title, 40),
                ['type' => 'recipe_comment', 'recipe_id' => (string) $recipe->id]);
        }

        return redirect($recipe->url.'#comments')->with('ok', '댓글이 등록되었습니다.');
    }

    public function destroyComment(Request $request, \App\Models\RecipeComment $comment)
    {
        abort_unless($request->user()->is_admin || $comment->user_id === $request->user()->id, 403);
        $recipe = $comment->recipe;
        $comment->delete();
        if ($recipe) {
            $recipe->decrement('comment_count');
        }

        return back()->with('ok', '댓글을 삭제했습니다.');
    }

    /** 태그별 레시피 목록(SEO) */
    public function tag(string $tag)
    {
        $tag = trim($tag);
        $recipes = Recipe::published()->with('category')
            ->where(fn ($q) => $q->where('tags', $tag)
                ->orWhere('tags', 'like', $tag.',%')
                ->orWhere('tags', 'like', '%,'.$tag)
                ->orWhere('tags', 'like', '%,'.$tag.',%'))
            ->latest('published_at')->paginate(16)->withQueryString();

        return view('community.recipes.tag', compact('tag', 'recipes'));
    }

    /* ===== 내부 ===== */
    private function authorizeOwner(Request $request, Recipe $recipe): void
    {
        abort_unless($recipe->user_id && ($recipe->user_id === $request->user()->id || $request->user()->is_admin), 403);
    }

    private function validateWrite(Request $request): array
    {
        return $request->validate([
            'recipe_category_id' => ['required', 'exists:recipe_categories,id'],
            'title'     => ['required', 'string', 'max:200'],
            'summary'   => ['nullable', 'string', 'max:300'],
            'body'      => ['required', 'string', 'max:5000'],
            'video_url' => ['nullable', 'url', 'max:300'],
            'tags'      => ['nullable', 'string', 'max:200'],
            'photos.*'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ]);
    }

    private function fillWrite(Recipe $recipe, array $data, Request $request): void
    {
        $recipe->recipe_category_id = $data['recipe_category_id'];
        $recipe->title = $data['title'];
        $recipe->summary = $data['summary'] ?? null;
        // 사용자 입력은 평문 → 안전 HTML(이스케이프 + 줄바꿈)로 저장
        $recipe->body = '<p>'.nl2br(e(trim($data['body']))).'</p>';
        $recipe->video_url = trim((string) ($data['video_url'] ?? '')) ?: null;
        $recipe->tags = Recipe::normalizeTags($data['tags'] ?? null);
        if (blank($recipe->slug)) {
            $recipe->slug = Recipe::uniqueSlug($data['title'], (int) $data['recipe_category_id'], $recipe->id);
        }
    }

    private function handleGallery(Recipe $recipe, Request $request): void
    {
        if (! $request->hasFile('photos')) {
            return;
        }
        $dir = public_path('recipe/uploads');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $sort = (int) $recipe->images()->max('sort');
        foreach ($request->file('photos') as $file) {
            $name = now()->format('Ymd_His').'_'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)).'.'.strtolower($file->getClientOriginalExtension());
            $file->move($dir, $name);
            $path = '/recipe/uploads/'.$name;
            $recipe->images()->create(['path' => $path, 'sort' => ++$sort]);
            if (! $recipe->cover_image) {
                $recipe->cover_image = $path;
                $recipe->save();
            }
        }
    }
}
