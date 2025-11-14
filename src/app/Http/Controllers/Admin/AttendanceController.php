<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 全社員の勤怠一覧を表示（管理者）
     */
    public function index()
    {
        // 管理者ログインチェック
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $attendances = Attendance::with('user')
            ->orderBy('date', 'desc')
            ->paginate(20);

        return view('admin.attendance_list', compact('attendances'));
    }

    /**
     * 特定社員の1日勤怠詳細を表示（管理者）
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $attendance = Attendance::with(['user', 'breakTimes'])
            ->findOrFail($id);

        return view('admin.attendance_detail', compact('attendance'));
    }

    /**
     * 特定スタッフの勤怠履歴を表示
     */
    public function staffAttendances($id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $user = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)
            ->orderBy('date', 'desc')
            ->paginate(20);

        return view('admin.attendance_staff_list', compact('user', 'attendances'));
    }
}
