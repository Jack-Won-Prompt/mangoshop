<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RestockAlert;
use Illuminate\Support\Facades\Auth;

/**
 * 재입고 알림 신청/해제.
 */
class RestockController extends Controller
{
    public function toggle(Product $product)
    {
        $existing = RestockAlert::where('user_id', Auth::id())
            ->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
            $msg = '재입고 알림을 해제했습니다.';
        } else {
            RestockAlert::create([
                'user_id'    => Auth::id(),
                'product_id' => $product->id,
            ]);
            $msg = '재입고 시 알림을 보내드립니다.';
        }

        return back()->with('ok', $msg);
    }
}
