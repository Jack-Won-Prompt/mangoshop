<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCashback extends Model
{
    protected $fillable = [
        'agent_id', 'order_id', 'buyer_name', 'order_amount', 'rate', 'amount', 'status',
    ];

    protected $casts = ['rate' => 'decimal:2'];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
