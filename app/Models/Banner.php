<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'image', 'link', 'bg_color', 'position', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** 저장된 image를 현재 호스트(APP_URL) 기준 URL로 정규화 */
    public function getImageUrlAttribute(): ?string
    {
        return Media::url($this->image);
    }
}
