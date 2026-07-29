<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SellerInviteMail;
use App\Models\SellerInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SellerInviteController extends Controller
{
    public function index()
    {
        $invites = SellerInvite::with(['inviter'])->latest()->paginate(30);
        $stats = [
            'total'    => SellerInvite::count(),
            'pending'  => SellerInvite::where('status', 'pending')->count(),
            'accepted' => SellerInvite::where('status', 'accepted')->count(),
        ];

        return view('admin.seller-invites.index', compact('invites', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email'        => ['required', 'email', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:100'],
            'origin_focus' => ['nullable', 'string', 'max:50'],
        ]);

        $invite = SellerInvite::create([
            'email'        => $data['email'],
            'company_name' => $data['company_name'] ?? null,
            'origin_focus' => $data['origin_focus'] ?? null,
            'invited_by'   => $request->user()->id,
        ]);

        return $this->dispatchMail($invite, '입점 초대 메일을 발송했습니다. ('.$invite->email.')');
    }

    public function resend(SellerInvite $invite)
    {
        if ($invite->status === 'accepted') {
            return back()->with('error', '이미 수락된 초대는 재발송할 수 없습니다.');
        }
        $invite->update([
            'status'     => 'pending',
            'token'      => Str::random(48),
            'expires_at' => now()->addDays(14),
        ]);

        return $this->dispatchMail($invite, '초대 메일을 재발송했습니다. ('.$invite->email.')');
    }

    public function revoke(SellerInvite $invite)
    {
        $invite->update(['status' => 'revoked']);

        return back()->with('ok', '초대를 취소했습니다.');
    }

    /** 메일 발송(실패해도 초대는 유지, 오류만 안내) */
    private function dispatchMail(SellerInvite $invite, string $okMsg)
    {
        try {
            Mail::to($invite->email)->send(new SellerInviteMail($invite));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '초대는 저장됐으나 메일 발송에 실패했습니다. 메일 설정을 확인하세요. ('.Str::limit($e->getMessage(), 120).')');
        }

        return back()->with('ok', $okMsg);
    }
}
