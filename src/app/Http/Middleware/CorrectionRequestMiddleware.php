<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorrectionRequestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 管理者ログイン中
        if (Auth::guard('admin')->check()) {
            $request->merge(['correction_role' => 'admin']);
            return $next($request);
        }

        // 一般ユーザー（web）ログイン中
        if (Auth::guard('web')->check()) {
            $request->merge(['correction_role' => 'user']);
            return $next($request);
        }

        // 未ログイン
        return redirect()->route('login');
    }
}
