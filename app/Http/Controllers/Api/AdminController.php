<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 모바일 관리자 API.
 *
 * 웹 어드민(`/admin`)의 실무 기능 중 현장에서 즉시 처리해야 하는 것만 노출한다.
 * (주문 상태/송장 · 도매 사업자 승인 · 문의 답변 · 후기 노출)
 * CSV 내보내기, 전용가 대량 업로드, 세금계산서 발행 등 데스크톱 작업은 제외.
 *
 * 상태 전이·재고·적립금 처리는 모두 웹 어드민과 동일한 모델 메서드
 * (`Order::markPaid()` / `Order::cancel()`)를 사용해 로직이 갈라지지 않게 한다.
 */
class AdminController extends Controller
{
    /** 대시보드 요약 */
    public function dashboard(Request $request)
    {
        $paidStatuses = ['paid', 'preparing', 'shipped', 'done'];

        return response()->json([
            'stats' => [
                'pending_orders'    => Order::where('status', 'pending')->count(),
                'pending_users'     => User::where('biz_status', 'pending')->count(),
                'pending_inquiries' => Inquiry::where('status', 'pending')->count(),
                'preparing_orders'  => Order::where('status', 'preparing')->count(),
                'total_products'    => Product::count(),
            ],
            'sales' => [
                'today'      => (int) Order::whereDate('created_at', today())
                    ->whereIn('status', $paidStatuses)->sum('total'),
                'today_count' => Order::whereDate('created_at', today())->count(),
                'month'      => (int) Order::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->whereIn('status', $paidStatuses)->sum('total'),
                'month_count' => Order::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)->count(),
            ],
            'recent_orders' => Order::with('user')->withCount('items')->latest()->take(8)->get()
                ->map(fn ($o) => $this->orderRow($o, $request)),
            'statuses' => Order::STATUSES,
        ]);
    }

    // ===================== 주문 =====================

    public function orders(Request $request)
    {
        $query = Order::with('user')->withCount('items')->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($q = trim((string) $request->get('q'))) {
            $query->where(fn ($w) => $w->where('order_no', 'like', "%{$q}%")
                ->orWhere('receiver_name', 'like', "%{$q}%")
                ->orWhere('depositor', 'like', "%{$q}%"));
        }

        $page = $query->paginate(20)->withQueryString();

        return response()->json([
            'orders'   => collect($page->items())->map(fn ($o) => $this->orderRow($o, $request)),
            'meta'     => $this->meta($page),
            'statuses' => Order::STATUSES,
        ]);
    }

    public function order(Request $request, Order $order)
    {
        $order->load('items.product', 'user');

        return response()->json([
            'order'    => array_merge(S::order($order, $request, true), [
                'customer' => $order->user ? [
                    'id'           => $order->user->id,
                    'name'         => $order->user->name,
                    'email'        => $order->user->email,
                    'phone'        => $order->user->phone,
                    'company_name' => $order->user->company_name,
                    'member_label' => $order->user->isWholesale() ? '도매회원' : '소매회원',
                ] : null,
            ]),
            'statuses' => Order::STATUSES,
        ]);
    }

    /** 주문 상태 변경 — 웹 어드민과 동일한 부수효과(적립금/재고/환불) */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
        ]);

        if ($data['status'] === 'paid') {
            $order->markPaid();          // 결제일 기록 + 적립금 지급(중복 방지)
        } elseif ($data['status'] === 'cancelled') {
            $res = $order->cancel('관리자 취소');   // 재고 복구 + 적립금 정산 + 환불
            if (! $res['ok']) {
                return response()->json(['message' => $res['message']], 422);
            }
        } else {
            $order->update(['status' => $data['status']]);
        }

        $order->refresh()->load('items.product', 'user');

        return response()->json([
            'message' => "주문 상태를 '{$order->statusLabel()}'(으)로 변경했습니다.",
            'order'   => S::order($order, $request, true),
        ]);
    }

    /** 송장 등록 → 배송중 전환 */
    public function updateOrderShipping(Request $request, Order $order)
    {
        $data = $request->validate([
            'courier'     => ['required', 'string', 'max:50'],
            'tracking_no' => ['required', 'string', 'max:60'],
        ]);

        $order->update([
            'courier'     => $data['courier'],
            'tracking_no' => $data['tracking_no'],
            'shipped_at'  => now(),
            'status'      => in_array($order->status, ['cancelled', 'done']) ? $order->status : 'shipped',
        ]);

        $order->refresh()->load('items.product', 'user');

        return response()->json([
            'message' => '송장이 등록되어 배송중 처리되었습니다.',
            'order'   => S::order($order, $request, true),
        ]);
    }

    // ===================== 회원 =====================

    public function users(Request $request)
    {
        $query = User::withCount('orders')->latest();

        match ($request->get('filter')) {
            'pending'   => $query->where('biz_status', 'pending'),
            'wholesale' => $query->whereIn('member_type', ['wholesale', 'business']),
            'retail'    => $query->whereNotIn('member_type', ['wholesale', 'business']),
            default     => null,
        };

        if ($q = trim((string) $request->get('q'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('company_name', 'like', "%{$q}%"));
        }

        $page = $query->paginate(20)->withQueryString();

        return response()->json([
            'users'         => collect($page->items())->map(fn ($u) => $this->userRow($u)),
            'meta'          => $this->meta($page),
            'pending_count' => User::where('biz_status', 'pending')->count(),
        ]);
    }

    public function user(Request $request, User $user)
    {
        $user->loadCount('orders');

        return response()->json([
            'user' => array_merge(S::user($user), [
                'orders_count' => (int) $user->orders_count,
                'created_at'   => $user->created_at?->format('Y-m-d'),
                'biz_file'     => S::image($user->biz_file ?? null, $request),
            ]),
            'recent_orders' => $user->orders()->withCount('items')->latest()->take(5)->get()
                ->map(fn ($o) => $this->orderRow($o, $request)),
        ]);
    }

    /** 도매 사업자 승인/반려 */
    public function approveUser(Request $request, User $user)
    {
        $data = $request->validate([
            'biz_status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'grade'      => ['nullable', Rule::in(['basic', 'silver', 'gold'])],
        ]);

        $user->update([
            'biz_status' => $data['biz_status'],
            'grade'      => $data['grade'] ?? $user->grade ?? 'basic',
        ]);

        $label = ['approved' => '승인', 'rejected' => '반려', 'pending' => '대기'][$data['biz_status']];

        return response()->json([
            'message' => "회원을 {$label} 처리했습니다.",
            'user'    => $this->userRow($user->fresh()),
        ]);
    }

    // ===================== 문의 =====================

    public function inquiries(Request $request)
    {
        $query = Inquiry::with('user')->latest();
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $page = $query->paginate(20)->withQueryString();

        return response()->json([
            'inquiries' => collect($page->items())->map(fn ($i) => $this->inquiryRow($i)),
            'meta'      => $this->meta($page),
            'types'     => Inquiry::TYPES,
            'pending_count' => Inquiry::where('status', 'pending')->count(),
        ]);
    }

    public function answerInquiry(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'answer' => ['required', 'string', 'max:5000'],
        ]);

        $inquiry->update([
            'answer'      => $data['answer'],
            'status'      => 'answered',
            'answered_at' => now(),
        ]);

        return response()->json([
            'message' => '답변이 등록되었습니다.',
            'inquiry' => $this->inquiryRow($inquiry->fresh()),
        ]);
    }

    // ===================== 후기 =====================

    public function reviews(Request $request)
    {
        $query = Review::with('product')->latest();
        match ($request->get('filter')) {
            'hidden'  => $query->where('is_hidden', true),
            'visible' => $query->where('is_hidden', false),
            default   => null,
        };
        if ($q = trim((string) $request->get('q'))) {
            $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")
                ->orWhere('body', 'like', "%{$q}%")
                ->orWhere('author_name', 'like', "%{$q}%"));
        }

        $page = $query->paginate(20)->withQueryString();

        return response()->json([
            'reviews' => collect($page->items())->map(fn ($r) => array_merge(S::review($r), [
                'is_hidden'    => (bool) $r->is_hidden,
                'product_name' => $r->product?->name,
                'product_slug' => $r->product?->slug,
            ])),
            'meta' => $this->meta($page),
        ]);
    }

    public function toggleReview(Request $request, Review $review)
    {
        $review->update(['is_hidden' => ! $review->is_hidden]);

        return response()->json([
            'message'   => $review->is_hidden ? '후기를 숨겼습니다.' : '후기를 노출했습니다.',
            'is_hidden' => (bool) $review->is_hidden,
        ]);
    }

    public function destroyReview(Request $request, Review $review)
    {
        $review->delete();

        return response()->json(['message' => '후기를 삭제했습니다.']);
    }

    // ===================== 헬퍼 =====================

    private function orderRow(Order $o, Request $request): array
    {
        return array_merge(S::order($o, $request), [
            'customer_name' => $o->relationLoaded('user') && $o->user ? $o->user->name : null,
        ]);
    }

    private function userRow(User $u): array
    {
        return [
            'id'           => $u->id,
            'name'         => $u->name,
            'email'        => $u->email,
            'phone'        => $u->phone,
            'member_type'  => $u->member_type,
            'is_wholesale' => $u->isWholesale(),
            'biz_status'   => $u->biz_status,
            'grade'        => $u->grade,
            'company_name' => $u->company_name,
            'biz_no'       => $u->biz_no,
            'biz_type'     => $u->biz_type,
            'point'        => (int) $u->point,
            'is_admin'     => (bool) $u->is_admin,
            'orders_count' => (int) ($u->orders_count ?? 0),
            'created_at'   => $u->created_at?->format('Y-m-d'),
        ];
    }

    private function inquiryRow(Inquiry $i): array
    {
        return [
            'id'          => $i->id,
            'type'        => $i->type,
            'type_label'  => $i->typeLabel(),
            'name'        => $i->name,
            'phone'       => $i->phone,
            'email'       => $i->email,
            'subject'     => $i->subject,
            'body'        => $i->body,
            'status'      => $i->status,
            'is_answered' => $i->status === 'answered',
            'answer'      => $i->answer,
            'answered_at' => $i->answered_at?->format('Y-m-d H:i'),
            'is_secret'   => (bool) $i->is_secret,
            'created_at'  => $i->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function meta($page): array
    {
        return [
            'current_page' => $page->currentPage(),
            'last_page'    => $page->lastPage(),
            'total'        => $page->total(),
            'has_more'     => $page->hasMorePages(),
        ];
    }
}
