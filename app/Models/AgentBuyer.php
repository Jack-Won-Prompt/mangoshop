<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentBuyer extends Model
{
    protected $fillable = ['agent_id', 'name', 'biz_no', 'phone', 'memo'];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
