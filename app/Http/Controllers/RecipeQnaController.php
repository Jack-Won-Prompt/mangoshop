<?php

namespace App\Http\Controllers;

use App\Models\RecipeAnswer;
use App\Models\RecipeCategory;
use App\Models\RecipeQuestion;
use App\Support\AdminPush;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 레시피 물어보기(Q&A) — 사용자 질문 + 사용자·관리자 답변.
 * 열람은 공개, 작성은 로그인 필수 + 허니팟 + 레이트리밋.
 */
class RecipeQnaController extends Controller
{
    public function index(Request $request)
    {
        $questions = RecipeQuestion::published()->with('user', 'category')
            ->latest()->paginate(15)->withQueryString();
        $categories = RecipeCategory::active()->orderBy('sort_order')->get();

        return view('community.recipes.qna.index', compact('questions', 'categories'));
    }

    public function ask()
    {
        return view('community.recipes.qna.ask', ['categories' => RecipeCategory::active()->orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        if (filled($request->input('website'))) {           // 허니팟
            return redirect()->route('community.recipe.qna');
        }
        $key = 'recipe_q:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput()->with('error', '질문이 너무 자주 등록되었습니다. 잠시 후 다시 시도해 주세요.');
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'recipe_category_id' => ['nullable', 'exists:recipe_categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'body'  => ['required', 'string', 'max:3000'],
        ]);

        $q = RecipeQuestion::create($data + ['user_id' => $request->user()->id, 'status' => 'published']);

        // 관리자 앱 알림
        AdminPush::toAdmins('🍳 새 레시피 질문', $request->user()->name.' · '.\Illuminate\Support\Str::limit($q->title, 40),
            ['type' => 'recipe_question', 'question_id' => (string) $q->id]);

        return redirect()->route('community.recipe.qna.show', $q)->with('ok', '질문이 등록되었습니다.');
    }

    public function show(RecipeQuestion $question)
    {
        abort_unless($question->status === 'published', 404);

        $key = 'rq_viewed_'.$question->id;
        if (! session()->has($key)) {
            $question->increment('view_count');
            session()->put($key, true);
        }
        $question->load('user', 'category', 'answers.user');

        return view('community.recipes.qna.show', compact('question'));
    }

    public function answer(Request $request, RecipeQuestion $question)
    {
        abort_unless($question->status === 'published', 404);

        if (filled($request->input('website'))) {
            return redirect()->route('community.recipe.qna.show', $question);
        }
        $key = 'recipe_a:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 15)) {
            return back()->with('error', '답변이 너무 자주 등록되었습니다. 잠시 후 다시 시도해 주세요.');
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $isAdmin = (bool) $request->user()->is_admin;
        RecipeAnswer::create([
            'recipe_question_id' => $question->id,
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
            'is_admin' => $isAdmin,
            'status'  => 'published',
        ]);
        $question->increment('answer_count');

        // 질문자에게(관리자 답변이면) 알림은 생략 — 앱 알림은 관리자 대상만 유지
        if (! $isAdmin) {
            AdminPush::toAdmins('💬 레시피 질문 새 답변', \Illuminate\Support\Str::limit($question->title, 40),
                ['type' => 'recipe_answer', 'question_id' => (string) $question->id]);
        }

        return redirect()->route('community.recipe.qna.show', $question)->with('ok', '답변이 등록되었습니다.');
    }
}
