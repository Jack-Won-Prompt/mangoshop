<?php

namespace App\Services;

use App\Mail\OrderStatementMail;
use App\Models\Order;
use App\Models\OrderStatement;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * 주문 기반 거래명세서(PDF) 생성·다운로드·이메일 발송 + 발행 이력.
 *  - 공급자: config('site') / config('popbill.supplier')
 *  - 공급받는자: 주문 회원(사업자면 상호/사업자번호, 아니면 받는분)
 *  - 금액은 부가세 포함가로 간주 (세금계산서 로직과 동일: 공급가 = round(total/1.1))
 */
class OrderStatementService
{
    /** dompdf 문서 생성 */
    public function pdf(Order $order, ?int $seq = null)
    {
        $order->loadMissing('items', 'user', 'seller');

        return Pdf::loadView('print.order-statement', [
            'order'         => $order,
            'statementDate' => $order->paid_at ?: $order->created_at,
            'seq'           => $seq ?? ($this->lastSeq($order) + 1),
            'isTaxable'     => $this->isTaxable($order),
        ])->setPaper('a4');
    }

    /** 브라우저 미리보기(이력 남기지 않음) */
    public function stream(Order $order)
    {
        return $this->pdf($order)->stream('statement_'.$order->order_no.'.pdf');
    }

    /** PDF 다운로드 (+ 이력 기록). 한글 파일명 대응 */
    public function download(Order $order, ?int $issuedBy = null)
    {
        $seq = $this->lastSeq($order) + 1;
        $pdf = $this->pdf($order, $seq);
        $name = $this->fileName($order, $seq);

        $this->log($order, $seq, $name, 'download', null, $issuedBy);

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $name,
                'statement_'.$order->order_no.'.pdf',
            ),
        ]);
    }

    /**
     * 거래명세서 이메일 발송 (+ 이력 기록).
     * 반환: ['ok'=>bool, 'message'=>string, 'to'=>?string]
     */
    public function email(Order $order, ?string $to = null, ?int $issuedBy = null): array
    {
        $order->loadMissing('user');
        $to = $to ?: $order->user?->email;
        if (! $to) {
            return ['ok' => false, 'message' => '수신 이메일 주소가 없습니다.', 'to' => null];
        }

        $seq = $this->lastSeq($order) + 1;
        $name = $this->fileName($order, $seq);

        try {
            $pdf = $this->pdf($order, $seq);
            Mail::to($to)->send(new OrderStatementMail($order, $pdf->output(), $name));
            $this->log($order, $seq, $name, 'email', $to, $issuedBy);

            return ['ok' => true, 'message' => '거래명세서를 발송했습니다. ('.$to.')', 'to' => $to];
        } catch (\Throwable $e) {
            report($e);
            $this->log($order, $seq, $name, 'email', $to, $issuedBy, $e->getMessage());

            return ['ok' => false, 'message' => '메일 발송 실패: '.\Illuminate\Support\Str::limit($e->getMessage(), 120), 'to' => $to];
        }
    }

    /* ===== 헬퍼 ===== */

    /** 과세 여부(하나라도 과세면 과세) */
    public function isTaxable(Order $order): bool
    {
        $ids = $order->items->pluck('product_id')->filter();
        if ($ids->isEmpty()) {
            return true;
        }

        return Product::whereIn('id', $ids)->where('tax_type', '!=', 'exempt')->exists();
    }

    private function lastSeq(Order $order): int
    {
        return (int) OrderStatement::where('order_id', $order->id)->max('seq');
    }

    private function fileName(Order $order, int $seq): string
    {
        $buyer = $order->user?->company_name ?: ($order->receiver_name ?: '고객');
        $buyer = preg_replace('/[\/\\\\:*?"<>|]+/', '', $buyer);
        $suffix = $seq > 1 ? '_'.$seq.'회차' : '';

        return '거래명세서_'.$buyer.'_'.$order->order_no.$suffix.'.pdf';
    }

    private function log(Order $order, int $seq, string $name, string $action, ?string $to, ?int $issuedBy, ?string $error = null): void
    {
        OrderStatement::create([
            'order_id'       => $order->id,
            'seq'            => $seq,
            'file_name'      => $name,
            'statement_date' => ($order->paid_at ?: $order->created_at)?->toDateString(),
            'total_amount'   => (int) $order->total,
            'action'         => $action,
            'sent_to'        => $to,
            'issued_by'      => $issuedBy,
            'error_message'  => $error,
        ]);
    }
}
