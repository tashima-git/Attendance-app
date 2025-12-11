<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Requests\AttendanceAdminUpdateRequest;

class AttendanceController extends Controller
{
    /**
     * 管理者：特定日の勤怠一覧
     */
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        if (!$this->isValidDate($date)) {
            $date = Carbon::today()->format('Y-m-d');
        }

        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $date)
            ->orderBy('user_id')
            ->get()
            ->map(fn($a) => $this->calculateTimes($a));

        return view('admin.attendance.list', compact('attendances', 'date'));
    }

    /**
     * 管理者：勤怠詳細
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);
        $attendance = $this->calculateTimes($attendance);

        return view('admin.attendance.detail', compact('attendance'));
    }

    /**
     * 管理者：スタッフ別 月次勤怠一覧
     */
    public function staffAttendances(Request $request, $id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $user = User::findOrFail($id);

        $month = $request->query('month', Carbon::now()->format('Y-m'));
        if (!$this->isValidMonth($month)) {
            $month = Carbon::now()->format('Y-m');
        }

        $carbonMonth = Carbon::parse($month);
        $start = $carbonMonth->copy()->startOfMonth();
        $end   = $carbonMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date')
            ->get();

        $attendanceMap = [];
        foreach ($attendances as $attendance) {
            $key = is_object($attendance->work_date)
                ? Carbon::parse($attendance->work_date)->format('Y-m-d')
                : Carbon::parse($attendance->work_date)->format('Y-m-d');

            $attendanceMap[$key] = $this->calculateTimes($attendance);
        }

        return view('admin.attendance.staff-monthly', compact(
            'user', 'month', 'start', 'end', 'attendanceMap'
        ));
    }

    /**
     * 管理者：勤怠更新（編集）
     */
    public function update(AttendanceAdminUpdateRequest $request, $id)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);
        $validated = $request->validated();

        // 出退勤更新
        $attendance->clock_in  = $request->filled('clock_in')  ? $attendance->work_date . ' ' . $request->clock_in : null;
        $attendance->clock_out = $request->filled('clock_out') ? $attendance->work_date . ' ' . $request->clock_out : null;
        $attendance->remarks   = $request->remarks;
        $attendance->save();

        // 休憩時間更新
        $attendance->breakTimes()->delete();
        if ($request->has('breaks')) {
            foreach ($request->breaks as $break) {
                if (!empty($break['break_start']) || !empty($break['break_end'])) {
                    $attendance->breakTimes()->create([
                        'break_start' => $attendance->work_date . ' ' . ($break['break_start'] ?? null),
                        'break_end'   => $attendance->work_date . ' ' . ($break['break_end'] ?? null),
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.attendance.show', $attendance->id)
            ->with('success', '勤怠を更新しました。');
    }

    /* =========================
       勤怠時間計算ロジック
       ========================= */

    private function calculateTimes($attendance)
    {
        // 勤務時間（分）
        if ($attendance->clock_in && $attendance->clock_out) {
            $start = Carbon::parse($attendance->clock_in);
            $end   = Carbon::parse($attendance->clock_out);
            $attendance->work_total = $start->diffInMinutes($end);
        } else {
            $attendance->work_total = null;
        }

        // 休憩（分）
        $breakMinutes = 0;
        foreach ($attendance->breakTimes as $break) {
            if ($break->break_start && $break->break_end) {
                $breakStart = Carbon::parse($break->break_start);
                $breakEnd   = Carbon::parse($break->break_end);
                $breakMinutes += $breakStart->diffInMinutes($breakEnd);
            }
        }
        $attendance->break_total = $breakMinutes;

        // 実働（分）
        $attendance->real_work_total =
            $attendance->work_total !== null
                ? max($attendance->work_total - $attendance->break_total, 0)
                : null;

        // 表示用 HH:MM
        $attendance->work_hm = $attendance->work_total !== null
            ? $this->minutesToHm($attendance->work_total)
            : '';

        $attendance->break_hm = $attendance->break_total > 0
            ? $this->minutesToHm($attendance->break_total)
            : '';

        $attendance->real_work_hm = $attendance->real_work_total !== null
            ? $this->minutesToHm($attendance->real_work_total)
            : '';

        // Blade 互換名
        $attendance->total_work_time_hm = $attendance->real_work_hm;
        $attendance->total_work_time_minutes = $attendance->real_work_total;

        return $attendance;
    }

    private function minutesToHm($minutes)
    {
        if ($minutes === null) return '';
        $hours = intdiv((int)$minutes, 60);
        $mins  = (int)$minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    /* =========================
       日付バリデーション
       ========================= */

    private function isValidDate($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            && Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
    }

    private function isValidMonth($month)
    {
        return preg_match('/^\d{4}-\d{2}$/', $month)
            && Carbon::createFromFormat('Y-m', $month)->format('Y-m') === $month;
    }
}
