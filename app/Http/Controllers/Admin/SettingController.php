<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function edit()
    {
        // config('site')는 AppServiceProvider에서 DB설정과 병합된 최종값
        return view('admin.settings.edit', [
            'site' => config('site'),
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
            // 메인 혜택 안내 스트립(4칸)
            'strip'              => ['nullable', 'array'],
            'strip.*.icon'       => ['nullable', 'string', 'max:30'],
            'strip.*.title'      => ['nullable', 'string', 'max:40'],
            'strip.*.desc'       => ['nullable', 'string', 'max:80'],
            // 메인 피처 밴드 2블록 (적립·캐시백 / 수입·유통)
            'feat'               => ['nullable', 'array'],
            'feat.*.kicker'      => ['nullable', 'string', 'max:40'],
            'feat.*.title'       => ['nullable', 'string', 'max:200'],
            'feat.*.desc'        => ['nullable', 'string', 'max:400'],
            'feat.*.chips'       => ['nullable', 'string', 'max:300'],
            'feat.*.button'      => ['nullable', 'string', 'max:30'],
            'feat.*.link'        => ['nullable', 'string', 'max:300', $noScriptUrl],
            'feat.*.image_path'  => ['nullable', 'string', 'max:300', $noScriptUrl],
            'feat.*.image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [
            'feat.*.image.max'         => '피처 이미지는 5MB 이하만 업로드할 수 있습니다.',
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

        $site = array_merge(config('site'), array_diff_key($plain, array_flip(['banks', 'popular_keywords', 'inquiry_emails', 'home_show_recipe', 'feat', 'strip'])), [
            'banks'            => $banks,
            'popular_keywords' => $keywords,
            'inquiry_emails'   => $inquiryEmails,
            'home_show_recipe' => $request->boolean('home_show_recipe'),   // 체크박스: 미체크=false 명시 저장
            'home_features'    => $this->featuresFrom($request),
            'home_strip'       => $this->stripFrom($request),
        ]);

        Setting::put('site', $site);

        return back()->with('ok', '사이트 설정이 저장되었습니다.');
    }

    /** 메인 혜택 안내 스트립(4칸) 입력을 기존 순서/기본값 위에 병합 */
    private function stripFrom(Request $request): array
    {
        $current = config('site.home_strip', []);
        $input   = (array) $request->input('strip', []);

        foreach ($input as $i => $s) {
            $block = $current[$i] ?? [];
            $current[$i] = [
                'icon'  => trim((string) ($s['icon'] ?? ($block['icon'] ?? 'check'))) ?: 'check',
                'title' => trim((string) ($s['title'] ?? ($block['title'] ?? ''))),
                'desc'  => trim((string) ($s['desc'] ?? ($block['desc'] ?? ''))),
            ];
        }

        return array_values($current);
    }

    /** 메인 피처 밴드(적립·캐시백 / 수입·유통) 입력을 기존 순서/기본값 위에 병합 */
    private function featuresFrom(Request $request): array
    {
        $current = config('site.home_features', []);
        $input   = (array) $request->input('feat', []);
        $files   = (array) $request->file('feat', []);

        foreach ($input as $i => $f) {
            $block = $current[$i] ?? [];

            // 이미지: 새 파일 업로드 우선 → 경로 직접입력 → 기존값 유지
            $image = trim((string) ($f['image_path'] ?? ($block['image'] ?? '')));
            if (! empty($files[$i]['image'])) {
                $image = $this->saveUploadImage($files[$i]['image'], 'home');
            }

            $chips = collect(explode(',', (string) ($f['chips'] ?? '')))
                ->map(fn ($c) => trim($c))->filter()->values()->all();

            $current[$i] = [
                'kicker' => trim((string) ($f['kicker'] ?? ($block['kicker'] ?? ''))),
                'title'  => \App\Support\Media::headline($f['title'] ?? ($block['title'] ?? '')),
                'desc'   => trim((string) ($f['desc'] ?? ($block['desc'] ?? ''))),
                'chips'  => $chips,
                'button' => trim((string) ($f['button'] ?? ($block['button'] ?? ''))),
                'link'   => trim((string) ($f['link'] ?? ($block['link'] ?? ''))),
                'image'  => $image,
            ];
        }

        return array_values($current);
    }

    /** 사이트설정 이미지를 public/product/uploads/{sub} 에 저장하고 상대경로 반환 */
    private function saveUploadImage($file, string $sub): string
    {
        $dir = public_path('product/uploads/'.$sub);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'jpg'));
        $name = now()->format('Ymd_His').'_'.Str::lower(Str::random(6)).'.'.$ext;
        $file->move($dir, $name);

        return 'product/uploads/'.$sub.'/'.$name;
    }
}
