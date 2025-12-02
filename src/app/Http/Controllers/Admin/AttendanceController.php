<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 管理者：特定日の勤怠一覧（日ごと）
     */
    public function index(Request $request)
    {
        // 管理者ログインチェック
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        // 指定日（デフォルト今日）
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        // 指定日の勤怠一覧取得（休憩も一緒に取得）
        $attendances = Attendance::with('user', 'breakTimes')
            ->whereDate('work_date', $date)
            ->orderBy('user_id')
            ->get()
            ->map(function ($attendance) {
                return $this->calculateTimes($attendance);
            });

        return view('admin.attendance.list', [
            'attendances' => $attendances,
            'date' => $date,
        ]);
    }

    /**
     * 管理者：1日の勤怠詳細
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $attendance = Attendance::with(['user', 'breakTimes'])
            ->findOrFail($id);

        // 時間計算
        $attendance = $this->calculateTimes($attendance);

        return view('admin.attendance.detail', compact('attendance'));
    }

    /**
     * 管理者：特定スタッフの勤怠履歴
     */
    public function staffAttendances($id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $user = User::findOrFail($id);

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $id)
            ->orderBy('work_date', 'desc')
            ->paginate(20);

        // 個別計算
        $attendances->getCollection()->transform(function ($attendance) {
            return $this->calculateTimes($attendance);
        });

        return view('admin.attendance.staff_list', compact('user', 'attendances'));
    }

    /**
     * 勤務時間 & 休憩時間の計算
     */
    private function calculateTimes($attendance)
    {
        // 出勤・退勤
        if ($attendance->clock_in && $attendance->clock_out) {
            $start = Carbon::parse($attendance->clock_in);
            $end   = Carbon::parse($attendance->clock_out);
            $attendance->work_total = $start->diffInMinutes($end);
        } else {
            $attendance->work_total = null;
        }

        // 休憩合計時間
        $breakMinutes = 0;

        foreach ($attendance->breakTimes as $break) {
            if ($break->break_start && $break->break_end) {
                $breakStart = Carbon::parse($break->break_start);
                $breakEnd   = Carbon::parse($break->break_end);
                $breakMinutes += $breakStart->diffInMinutes($breakEnd);
            }
        }

        $attendance->break_total = $breakMinutes;

        // 実働時間（勤務時間 − 休憩時間）
        if (!is_null($attendance->work_total)) {
            $attendance->real_work_total = max($attendance->work_total - $attendance->break_total, 0);
        } else {
            $attendance->real_work_total = null;
        }

        return $attendance;
    }
}
