<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q = LoginHistory::with('user')->latest();

        if ($request->filled('email')) {
            $q->where('email', 'like', '%'.$request->string('email').'%');
        }
        if (in_array($request->get('status'), ['success', 'fail'], true)) {
            $q->where('status', $request->get('status'));
        }

        $histories = $q->paginate(30)->withQueryString();

        $stats = [
            'total'   => LoginHistory::count(),
            'success' => LoginHistory::where('status', 'success')->count(),
            'fail'    => LoginHistory::where('status', 'fail')->count(),
            'today'   => LoginHistory::whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('admin.login-history.index', compact('histories', 'stats'));
    }
}
