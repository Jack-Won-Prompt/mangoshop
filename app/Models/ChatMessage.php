<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['chat_room_id', 'sender', 'admin_id', 'body'];

    protected static function booted(): void
    {
        // 고객(user) 메시지 생성 시 관리자 앱으로 FCM 알림 (web/app 모든 경로 커버)
        static::created(function (self $m) {
            if ($m->sender !== 'user') {
                return;
            }
            $room = $m->room;
            \App\Support\AdminPush::toAdmins(
                '💬 새 상담 메시지',
                ($room?->displayName() ?: '고객').' · '.\Illuminate\Support\Str::limit($m->body, 40),
                ['type' => 'chat', 'room_id' => (string) $m->chat_room_id, 'room_token' => (string) ($room?->token ?? '')],
            );
        });
    }

    public function room()
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    /** 위젯/콘솔 표시용 직렬화 */
    public function toWire(): array
    {
        return [
            'id'     => $this->id,
            'sender' => $this->sender,
            'body'   => $this->body,
            'time'   => $this->created_at->format('H:i'),
            'date'   => $this->created_at->format('Y-m-d'),
        ];
    }
}
