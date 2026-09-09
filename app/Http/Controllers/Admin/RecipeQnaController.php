<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecipeAnswer;
use App\Models\RecipeQuestion;

/**
 * 레시피 Q&A 모더레이션 — 질문/답변 숨김·삭제(관리자).
 */
class RecipeQnaController extends Controller
{
    public function index()
    {
        $questions = RecipeQuestion::with('user', 'category')->withCount('answers')->latest()->paginate(20);

        return view('admin.recipes.qna', compact('questions'));
    }

    public function toggleQuestion(RecipeQuestion $question)
    {
        $question->update(['status' => $question->status === 'published' ? 'hidden' : 'published']);

        return back()->with('ok', '질문 상태를 변경했습니다.');
    }

    public function destroyQuestion(RecipeQuestion $question)
    {
        $question->delete();

        return back()->with('ok', '질문을 삭제했습니다.');
    }

    public function destroyAnswer(RecipeAnswer $answer)
    {
        $q = $answer->question;
        $answer->delete();
        if ($q) {
            $q->decrement('answer_count');
        }

        return back()->with('ok', '답변을 삭제했습니다.');
    }
}
