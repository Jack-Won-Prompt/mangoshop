<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestockAlert extends Model
{
    protected $fillable = ['user_id', 'product_id', 'notified_at'];

    protected $casts = ['notified_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
