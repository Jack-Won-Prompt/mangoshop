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
            ->orderByDesc('view_count')->take(8)->get();

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
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('view_count'), fn ($q) => $q->latest('published_at'))
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
}
