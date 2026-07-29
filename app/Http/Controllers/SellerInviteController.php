<?php

namespace App\Http\Controllers;

use App\Models\Seller;
use App\Models\SellerInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 입점 초대 수락(공개) — 초대 링크로 입점 신청 → User(판매자 대표계정) + Seller 생성.
 */
class SellerInviteController extends Controller
{
    public function show(string $token)
    {
        $invite = SellerInvite::where('token', $token)->firstOrFail();

        return view('seller.invite', compact('invite'));
    }

    public function accept(Request $request, string $token)
    {
        $invite = SellerInvite::where('token', $token)->firstOrFail();
        abort_unless($invite->isUsable(), 410, '유효하지 않거나 만료된 초대입니다.');

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:100'],
            'name'         => ['required', 'string', 'max:50'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'origin_focus' => ['nullable', 'string', 'max:50'],
            'biz_no'       => ['nullable', 'string', 'max:20'],
        ]);

        if (User::where('email', $invite->email)->exists()) {
            return back()->withInput()->with('error', '이미 가입된 이메일입니다. 로그인 후 고객센터로 문의해 주세요.');
        }

        $seller = DB::transaction(function () use ($invite, $data) {
            $user = User::create([
                'name'         => $data['name'],
                'email'        => $invite->email,
                'password'     => Hash::make($data['password']),
                'member_type'  => 'wholesale',
                'biz_status'   => 'approved',
                'company_name' => $data['company_name'],
                'biz_no'       => $data['biz_no'] ?? null,
                'phone'        => $data['phone'] ?? null,
            ]);

            $seller = Seller::create([
                'user_id'                 => $user->id,
                'name'                    => $data['company_name'],
                'ceo_name'                => $data['name'],
                'email'                   => $invite->email,
                'phone'                   => $data['phone'] ?? null,
                'biz_no'                  => $data['biz_no'] ?? null,
                'origin_focus'            => $data['origin_focus'] ?? $invite->origin_focus,
                'status'                  => 'approved',
                'is_active'               => true,
                'commission_rate'         => 10,
                'shipping_fee'            => 3000,
                'free_shipping_threshold' => 50000,
                'coldchain'               => true,
            ]);

            $invite->update([
                'status'           => 'accepted',
                'accepted_at'      => now(),
                'accepted_user_id' => $user->id,
            ]);

            Auth::login($user);

            return $seller;
        });

        return redirect()->route('seller.show', $seller->slug)
            ->with('ok', '입점이 완료되었습니다! 전용 스토어가 개설되었습니다.');
    }
}
