<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($cred, $request->boolean('remember'))) {
            // 탈퇴(비활성) 회원 차단
            if (Auth::user()->isWithdrawn()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => '탈퇴 처리된 계정입니다.']);
            }
            $request->session()->regenerate();
            \App\Services\ActivityLogger::login($request, Auth::user(), $cred['email'], 'success');
            $to = Auth::user()->is_admin ? route('admin.dashboard') : route('home');

            return redirect()->intended($to)->with('ok', '로그인되었습니다.');
        }

        \App\Services\ActivityLogger::login($request, null, $cred['email'], 'fail');

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => '이메일 또는 비밀번호가 올바르지 않습니다.']);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // 스팸 방지: 허니팟(봇) + IP 레이트리밋(1시간 5건)
        if (filled($request->input('website'))) {
            return redirect()->route('home');
        }
        $rlKey = 'register:'.$request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rlKey, 5)) {
            return back()->withInput()->with('error', '가입 시도가 너무 많습니다. 잠시 후 다시 시도해 주세요.');
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rlKey, 3600);

        $data = $request->validate([
            'member_type' => ['required', Rule::in(['general', 'business'])],
            'name'        => ['required', 'string', 'max:50'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'password'    => ['required', 'confirmed', 'min:8'],
            'phone'       => ['nullable', 'string', 'max:30'],
            // 사업자 전용
            'company_name' => ['required_if:member_type,business', 'nullable', 'string', 'max:100'],
            'biz_no'       => ['required_if:member_type,business', 'nullable', 'string', 'max:20'],
            'biz_type'     => ['nullable', 'string', 'max:50'],
            'agree_terms'     => ['accepted'],
            'agree_privacy'   => ['accepted'],
            'agree_marketing' => ['nullable', 'boolean'],
        ], [
            'agree_terms.accepted'   => '이용약관에 동의해 주세요.',
            'agree_privacy.accepted' => '개인정보 수집·이용에 동의해 주세요.',
        ]);

        $marketing = $request->boolean('agree_marketing');

        $isBusiness = $data['member_type'] === 'business';
        $signupPoint = config('site.signup_point', 0);

        $user = User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
            'phone'        => $data['phone'] ?? null,
            'member_type'  => $data['member_type'],
            'company_name' => $isBusiness ? $data['company_name'] : null,
            'biz_no'       => $isBusiness ? $data['biz_no'] : null,
            'biz_type'     => $isBusiness ? ($data['biz_type'] ?? null) : null,
            'biz_status'   => $isBusiness ? 'pending' : 'none',
            'point'        => $signupPoint,
            'marketing_agree'     => $marketing,
            'marketing_agreed_at' => $marketing ? now() : null,
        ]);

        // 가입 적립금 로그
        if ($signupPoint > 0) {
            $user->pointLogs()->create([
                'amount' => $signupPoint, 'balance' => $signupPoint, 'reason' => '신규가입 적립금',
            ]);
        }

        // 관리자 앱 FCM 알림
        \App\Support\AdminPush::toAdmins(
            '🙋 새 회원가입',
            $user->name.' · '.($isBusiness ? '사업자'.($user->company_name ? '('.$user->company_name.')' : '') : '일반').' · '.$user->email,
            ['type' => 'member_join', 'user_id' => (string) $user->id],
        );

        Auth::login($user);

        $msg = $isBusiness
            ? '회원가입이 완료되었습니다. 도매 승인 후 도매별 전용가가 적용됩니다.'
            : '회원가입이 완료되었습니다.';

        return redirect()->route('home')->with('ok', $msg);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
