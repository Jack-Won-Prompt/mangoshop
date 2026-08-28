<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'path', 'type', 'sort'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** 배포 호스트 기준 URL */
    public function getUrlAttribute(): ?string
    {
        return Media::url($this->path);
    }
}
