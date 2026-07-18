<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    protected $fillable = [
        'credit_account_id', 'order_id', 'type', 'amount', 'balance', 'memo',
    ];

    public function account()
    {
        return $this->belongsTo(CreditAccount::class, 'credit_account_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
