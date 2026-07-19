<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    protected $fillable = ['user_id', 'email', 'status', 'ip', 'user_agent'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 로그인 시도 기록 */
    public static function record(?User $user, string $email, string $status, \Illuminate\Http\Request $request): void
    {
        static::create([
            'user_id'    => $user?->id,
            'email'      => $email,
            'status'     => $status,
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);
    }
}
