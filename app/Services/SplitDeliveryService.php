<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

/**
 * 분할배송(엑셀=주문서) — CSV 파싱·검증 → 수령처별 배송 집계.
 * 컬럼: 상품코드, 수량, 받는사람, 전화번호, 우편번호, 주소, 상세주소, 요청사항
 */
class SplitDeliveryService
{
    public const MAX_ROWS = 200;

    public const HEADER = ['상품코드', '제품명', '수량', '받는사람', '전화번호', '우편번호', '주소', '상세주소', '요청사항'];

    /** CSV 원문(문자열) → 검증 결과 */
    public function parse(string $content, ?User $user): array
    {
        $content = $this->toUtf8($content);
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (! $lines || count($lines) < 2) {
            return $this->fail(['업로드된 파일에 데이터가 없습니다. 양식을 내려받아 작성해 주세요.']);
        }

        array_shift($lines); // 헤더 제거
        $errors = [];
        $rows = [];         // 유효 행: [product, qty, receiver...]
        $stockUse = [];     // product_id => 합계 수량
        $ln = 1;

        foreach ($lines as $line) {
            $ln++;
            if (trim($line) === '') {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                $errors[] = self::MAX_ROWS.'건까지만 처리됩니다. 초과 행은 무시되었습니다.';
                break;
            }
            $c = array_map(fn ($v) => trim((string) $v), str_getcsv($line));
            [$code, $pname, $qty, $name, $phone, $postcode, $addr1, $addr2, $memo] = array_pad($c, 9, '');

            if ($code === '' && $pname === '' && $name === '' && $addr1 === '') {
                continue; // 완전 빈 행
            }
            $rowErr = [];
            // 상품 매칭: 상품코드 우선, 코드가 비면 제품명으로 보조 매칭
            $product = null;
            if ($code !== '') {
                $product = Product::active()->where('code', $code)->first();
            } elseif ($pname !== '') {
                $product = Product::active()->where('name', $pname)->first();
            }
            if (! $product) {
                $rowErr[] = $code !== '' ? "상품코드 '{$code}' 없음" : "제품명 '{$pname}' 매칭 실패";
            }
            $q = (int) preg_replace('/[^0-9]/', '', (string) $qty);
            if ($q < 1) {
                $rowErr[] = '수량 오류';
            }
            if ($name === '') {
                $rowErr[] = '받는사람 누락';
            }
            if ($phone === '') {
                $rowErr[] = '전화번호 누락';
            }
            if ($addr1 === '') {
                $rowErr[] = '주소 누락';
            }

            if ($rowErr) {
                $errors[] = $ln.'행: '.implode(' · ', $rowErr);

                continue;
            }

            $stockUse[$product->id] = ($stockUse[$product->id] ?? 0) + $q;
            $rows[] = compact('product', 'q', 'name', 'phone', 'postcode', 'addr1', 'addr2', 'memo');
        }

        // 재고 초과 검증(상품 합계 기준)
        foreach ($stockUse as $pid => $used) {
            $p = Product::find($pid);
            if ($p && $used > $p->stock) {
                $errors[] = "재고 부족: {$p->name} (요청 {$used} / 재고 {$p->stock})";
            }
        }

        if (! $rows) {
            return $this->fail($errors ?: ['유효한 배송 행이 없습니다.']);
        }

        // 수령처별 그룹핑
        $groups = [];
        foreach ($rows as $r) {
            $key = $r['name'].'|'.$r['phone'].'|'.$r['postcode'].'|'.$r['addr1'].'|'.$r['addr2'];
            $unit = $r['product']->priceFor($user);
            $groups[$key]['receiver'] ??= [
                'name' => $r['name'], 'phone' => $r['phone'], 'postcode' => $r['postcode'],
                'address1' => $r['addr1'], 'address2' => $r['addr2'], 'memo' => $r['memo'],
            ];
            $groups[$key]['items'][] = [
                'product_id' => $r['product']->id,
                'seller_id'  => $r['product']->seller_id,
                'name'       => $r['product']->name,
                'code'       => $r['product']->code,
                'unit_label' => $r['product']->unit,
                'qty'        => $r['q'],
                'unit'       => $unit,
                'subtotal'   => $unit * $r['q'],
            ];
        }

        $shipments = [];
        $sumSub = $sumShip = $sumQty = 0;
        foreach ($groups as $g) {
            $sub = array_sum(array_column($g['items'], 'subtotal'));
            $qty = array_sum(array_column($g['items'], 'qty'));
            // 수령처마다 배송비: 기본 3,000(제주 5,000) + 3박스 단위 +2,000
            $ship = \App\Support\Shipping::fee($qty, $g['receiver']['postcode'] ?? null, $g['receiver']['address1'] ?? null);
            $shipments[] = [
                'receiver' => $g['receiver'],
                'items'    => $g['items'],
                'subtotal' => $sub,
                'shipping' => $ship,
                'total'    => $sub + $ship,
            ];
            $sumSub += $sub;
            $sumShip += $ship;
            $sumQty += $qty;
        }

        return [
            'ok'        => empty($errors),
            'errors'    => $errors,
            'shipments' => $shipments,
            'summary'   => [
                'recipients' => count($shipments),
                'qty'        => $sumQty,
                'subtotal'   => $sumSub,
                'shipping'   => $sumShip,
                'total'      => $sumSub + $sumShip,
            ],
        ];
    }

    /** 엑셀(CSV) 양식 문자열 — UTF-8 BOM */
    public function template(): string
    {
        $sample = ['GSP001', '프리미엄 애플망고 선물세트', '2', '홍길동', '010-1234-5678', '07789', '서울 강서구 마곡중앙로 161-8', 'C동 502호', '부재 시 경비실'];

        return "\xEF\xBB\xBF".$this->csvLine(self::HEADER)."\n".$this->csvLine($sample)."\n";
    }

    /* ===== 내부 ===== */

    private function fail(array $errors): array
    {
        return ['ok' => false, 'errors' => $errors, 'shipments' => [], 'summary' => ['recipients' => 0, 'qty' => 0, 'subtotal' => 0, 'shipping' => 0, 'total' => 0]];
    }

    private function toUtf8(string $s): string
    {
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // BOM 제거
        if (! mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'CP949, EUC-KR, UTF-8');
        }

        return $s;
    }

    private function csvLine(array $cols): string
    {
        return implode(',', array_map(function ($c) {
            $c = (string) $c;

            return (str_contains($c, ',') || str_contains($c, '"')) ? '"'.str_replace('"', '""', $c).'"' : $c;
        }, $cols));
    }
}
