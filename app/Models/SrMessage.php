<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SrMessage extends Model
{
    protected $fillable = [
        'sr_thread_id', 'role', 'content', 'attachments', 'tokens_in', 'tokens_out',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function thread()
    {
        return $this->belongsTo(SrThread::class, 'sr_thread_id');
    }

    /**
     * 어시스턴트가 답 끝에 붙이는 표시 — 이 건을 개발로 반영할 수 있는지 알린다.
     *
     * 표시를 글 안에 묻어 두는 까닭은, 이것 하나 때문에 표에 칸을 더하고 싶지 않아서다.
     * 관리자에게는 보이지 않도록 떼고 내보낸다.
     */
    private const APPLY_MARK = '~\s*\[\[APPLY:(YES|NO)\]\]\s*$~i';

    /** 화면에 보여 줄 내용 — 표시를 떼어 낸 것 */
    public function displayContent(): string
    {
        return trim((string) preg_replace(self::APPLY_MARK, '', (string) $this->content));
    }

    /** 개발로 반영할 수 있는 답인가 */
    public function canApply(): bool
    {
        return $this->role === 'assistant'
            && preg_match('~\[\[APPLY:YES\]\]~i', (string) $this->content) === 1;
    }
}
