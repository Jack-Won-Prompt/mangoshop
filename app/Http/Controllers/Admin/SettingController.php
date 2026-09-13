<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function edit()
    {
        // config('site')는 AppServiceProvider에서 DB설정과 병합된 최종값
        return view('admin.settings.edit', [
            'site'     => config('site'),
            // 메인 팝업 링크 선택용 판매중 상품 목록
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request)
    {
        // javascript:/data: 등 스크립트 실행 URL 차단
        $noScriptUrl = 'not_regex:/^\s*(javascript|data|vbscript):/i';

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:50'],
            'name_en'     => ['nullable', 'string', 'max:50'],
            'tagline'     => ['nullable', 'string', 'max:100'],
            'company'     => ['nullable', 'string', 'max:100'],
            'ceo'         => ['nullable', 'string', 'max:50'],
            'biz_no'      => ['nullable', 'string', 'max:50'],
            'mailorder'   => ['nullable', 'string', 'max:100'],
            'med_device'  => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string', 'max:200'],
            'cs_tel'      => ['nullable', 'string', 'max:50'],
            'cs_hours'    => ['nullable', 'string', 'max:150'],
            'email'       => ['nullable', 'string', 'max:100'],
            'payment_pg'     => ['required', 'in:toss,portone'],
            'free_ship_over' => ['required', 'integer', 'min:0'],
            'shipping_fee'   => ['required', 'integer', 'min:0'],
            'signup_point'   => ['required', 'integer', 'min:0'],
            'point_rate'     => ['required', 'integer', 'min:0', 'max:100'],
            'banks'              => ['array'],
            'banks.*.bank'       => ['nullable', 'string', 'max:50'],
            'banks.*.account'    => ['nullable', 'string', 'max:60'],
            'banks.*.holder'     => ['nullable', 'string', 'max:50'],
            'popular_keywords'   => ['nullable', 'string', 'max:500'],
            'home_new_title'     => ['nullable', 'string', 'max:60'],
            'home_new_sub'       => ['nullable', 'string', 'max:120'],
            'home_show_recipe'   => ['nullable', 'boolean'],
            'inquiry_emails'     => ['nullable', 'array'],
            'inquiry_emails.*'   => ['nullable', 'email', 'max:100'],
            // 메인 팝업
            'popup_enabled'      => ['nullable', 'boolean'],
            'popup_title'        => ['nullable', 'string', 'max:60'],
            'popup_sub'          => ['nullable', 'string', 'max:100'],
            'popup_button'       => ['nullable', 'string', 'max:30'],
            'popup_link'         => ['nullable', 'string', 'max:300', $noScriptUrl],
            'popup_new_window'   => ['nullable', 'boolean'],
            'popup_image_path'   => ['nullable', 'string', 'max:300', $noScriptUrl],
            'popup_image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'popup_start'        => ['nullable', 'date'],
            'popup_end'          => ['nullable', 'date', 'after_or_equal:popup_start'],
        ], [
            'popup_end.after_or_equal' => '팝업 종료일은 시작일 이후여야 합니다.',
            'popup_image.max'          => '팝업 이미지는 5MB 이하만 업로드할 수 있습니다.',
        ]);

        // 빈 계좌행 제거
        $banks = collect($request->input('banks', []))
            ->filter(fn ($b) => ! empty($b['bank']) && ! empty($b['account']))
            ->map(fn ($b) => [
                'bank'    => $b['bank'],
                'account' => $b['account'],
                'holder'  => $b['holder'] ?? '',
            ])->values()->all();

        // 인기검색어 콤마 분리
        $keywords = collect(explode(',', (string) $request->input('popular_keywords', '')))
            ->map(fn ($k) => trim($k))->filter()->values()->all();

        // 문의 알림 이메일 — 빈 값 제거·중복 제거(최대 3)
        $inquiryEmails = collect($request->input('inquiry_emails', []))
            ->map(fn ($e) => trim((string) $e))->filter()->unique()->take(3)->values()->all();

        // 팝업 입력(popup_*)은 site 최상위가 아닌 site.popup 으로 묶어 저장
        $plain = array_filter($data, fn ($k) => ! str_starts_with($k, 'popup_'), ARRAY_FILTER_USE_KEY);

        $site = array_merge(config('site'), array_diff_key($plain, array_flip(['banks', 'popular_keywords', 'inquiry_emails', 'home_show_recipe'])), [
            'banks'            => $banks,
            'popular_keywords' => $keywords,
            'inquiry_emails'   => $inquiryEmails,
            'home_show_recipe' => $request->boolean('home_show_recipe'),   // 체크박스: 미체크=false 명시 저장
            'popup'            => $this->popupFrom($request),
        ]);

        Setting::put('site', $site);

        return back()->with('ok', '사이트 설정이 저장되었습니다.');
    }

    private function popupFrom(Request $request): array
    {
        $image = trim((string) $request->input('popup_image_path', ''));
        if ($request->hasFile('popup_image')) {
            $image = $this->savePopupImage($request->file('popup_image'));
        }

        $date = fn (string $f) => $request->filled($f) ? Carbon::parse($request->input($f))->toDateString() : null;

        return [
            'enabled'    => $request->boolean('popup_enabled'),
            'title'      => trim((string) $request->input('popup_title', '')),
            'sub'        => trim((string) $request->input('popup_sub', '')),
            'button'     => trim((string) $request->input('popup_button', '')),
            'image'      => $image,
            'link'       => trim((string) $request->input('popup_link', '')),
            'new_window' => $request->boolean('popup_new_window'),
            'start'      => $date('popup_start'),
            'end'        => $date('popup_end'),
        ];
    }

    /** 팝업 이미지를 public/product/uploads/popup 에 저장하고 사이트 기준 상대경로를 반환 */
    private function savePopupImage($file): string
    {
        $dir = public_path('product/uploads/popup');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = now()->format('Ymd_His').'_'.Str::lower(Str::random(6)).'.'.strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);

        return 'product/uploads/popup/'.$name;
    }
}
