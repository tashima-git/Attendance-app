<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    /**
     * 全ユーザー（スタッフ）一覧を表示
     */
    public function index()
    {
        // 管理者ログインチェック
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        // ユーザー一覧を取得
        $users = User::orderBy('id', 'asc')->paginate(20);

        return view('admin.staff_list', compact('users'));
    }
}
