<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'image', 'acc1', 'acc2', 'acc3', 'link', 'bg_color', 'position', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** 저장된 image를 현재 호스트(APP_URL) 기준 URL로 정규화 */
    public function getImageUrlAttribute(): ?string
    {
        return Media::url($this->image);
    }

    /** 원형 포인트 이미지 URL(1~3). 비어 있으면 null → 블레이드에서 기본 이미지로 대체 */
    public function accUrl(int $n): ?string
    {
        return Media::url($this->{'acc'.$n});
    }
}
