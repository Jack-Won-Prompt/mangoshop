<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Popup extends Model
{
    protected $fillable = [
        'title', 'image', 'link', 'button', 'new_window',
        'is_active', 'sort_order', 'start_date', 'end_date',
    ];

    protected $casts = [
        'new_window' => 'boolean',
        'is_active'  => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /** 현재 노출 대상(활성 + 기간) 팝업 — 정렬 순서대로 */
    public static function visible()
    {
        $today = now()->startOfDay();

        return static::where('is_active', true)
            ->whereNotNull('image')
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }
}
