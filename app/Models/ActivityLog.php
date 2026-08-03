<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'type', 'user_id', 'session_id', 'platform', 'path', 'keyword',
        'product_id', 'product_name', 'result_count', 'email', 'status',
        'ip', 'user_agent', 'referer',
    ];

    public const TYPES = [
        'visit'        => '방문',
        'search'       => '상품검색',
        'product_view' => '상품조회',
        'login'        => '로그인',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function platformLabel(): string
    {
        return $this->platform === 'app' ? '앱' : '웹';
    }
}
