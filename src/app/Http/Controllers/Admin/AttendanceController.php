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
     * --------------------------------------------------------------
     * 管理者：特定日の勤怠一覧
     * GET /admin/attendance?date=YYYY-MM-DD
     * --------------------------------------------------------------
     */
    public function index(Request $request)
    {
        // 認証チェック
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        // 日付（デフォルト：今日）
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        // 日付バリデーション（不正値→今日へ）
        if (!$this->isValidDate($date)) {
            $date = Carbon::today()->format('Y-m-d');
        }

        // 当日分の勤怠
        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $date)
            ->orderBy('user_id')
            ->get()
            ->map(fn($a) => $this->calculateTimes($a));

        return view('admin.attendance.list', compact('attendances', 'date'));
    }


    /**
     * --------------------------------------------------------------
     * 管理者：勤怠詳細
     * GET /admin/attendance/{id}
     * --------------------------------------------------------------
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $attendance = Attendance::with(['user', 'breakTimes'])
            ->findOrFail($id);

        $attendance = $this->calculateTimes($attendance);

        return view('admin.attendance.detail', compact('attendance'));
    }


    /**
     * --------------------------------------------------------------
     * 管理者：スタッフ別 月次勤怠一覧
     * GET /admin/staff/{id}/attendances?month=YYYY-MM
     * --------------------------------------------------------------
     */
    public function staffAttendances(Request $request, $id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $user = User::findOrFail($id);

        // 選択月（デフォルト：今月）
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        // 月の妥当性チェック
        if (!$this->isValidMonth($month)) {
            $month = Carbon::now()->format('Y-m');
        }

        $carbonMonth = Carbon::parse($month);

        // 月初〜月末
        $start = $carbonMonth->copy()->startOfMonth();
        $end   = $carbonMonth->copy()->endOfMonth();

        // 月次データ
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date')
            ->get();

        // 日付→勤怠のマップ
        $attendanceMap = [];
        foreach ($attendances as $attendance) {
            $attendanceMap[$attendance->work_date] = $this->calculateTimes($attendance);
        }

        return view('admin.attendance.staff-monthly', compact(
            'user', 'month', 'start', 'end', 'attendanceMap'
        ));
    }


    /**
     * --------------------------------------------------------------
     * 管理者：勤怠更新（編集）
     * POST /admin/attendance/{id}
     * --------------------------------------------------------------
     */
    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // バリデーション
        $validated = $request->validate([
            'clock_in'  => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'remarks'   => 'nullable|string|max:200',
            'breaks.*.break_start' => 'nullable|date_format:H:i',
            'breaks.*.break_end'   => 'nullable|date_format:H:i|after:breaks.*.break_start',
        ]);

        // 出勤
        $attendance->clock_in = $request->filled('clock_in')
            ? $attendance->work_date . ' ' . $request->clock_in
            : null;

        // 退勤
        $attendance->clock_out = $request->filled('clock_out')
            ? $attendance->work_date . ' ' . $request->clock_out
            : null;

        // 備考更新
        $attendance->remarks = $request->remarks;
        $attendance->save();

        // 休憩は一度削除→再登録
        $attendance->breakTimes()->delete();

        if ($request->has('breaks')) {
            foreach ($request->breaks as $break) {
                if (!empty($break['break_start']) && !empty($break['break_end'])) {
                    $attendance->breakTimes()->create([
                        'break_start' => $attendance->work_date . ' ' . $break['break_start'],
                        'break_end'   => $attendance->work_date . ' ' . $break['break_end'],
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.attendance.show', $attendance->id)
            ->with('success', '勤怠を更新しました。');
    }


    /* ========================================================================
        勤怠時間計算ロジック
       ======================================================================== */

    /**
     * 勤務時間・休憩時間・実働時間を付与
     */
    private function calculateTimes($attendance)
    {
        // --- 勤務時間 ---
        if ($attendance->clock_in && $attendance->clock_out) {
            $start = Carbon::parse($attendance->clock_in);
            $end   = Carbon::parse($attendance->clock_out);
            $attendance->work_total = $start->diffInMinutes($end);
        } else {
            $attendance->work_total = null;
        }

        // --- 休憩 ---
        $breakMinutes = 0;
        foreach ($attendance->breakTimes as $break) {
            if ($break->break_start && $break->break_end) {
                $breakStart = Carbon::parse($break->break_start);
                $breakEnd   = Carbon::parse($break->break_end);
                $breakMinutes += $breakStart->diffInMinutes($breakEnd);
            }
        }

        $attendance->break_total = $breakMinutes;

        // --- 実働 ---
        $attendance->real_work_total =
            $attendance->work_total !== null
                ? max($attendance->work_total - $attendance->break_total, 0)
                : null;

        return $attendance;
    }


    /* ========================================================================
        日付バリデーション
       ======================================================================== */

    /**
     * YYYY-MM-DD の妥当性チェック
     */
    private function isValidDate($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            && Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
    }

    /**
     * YYYY-MM の妥当性チェック
     */
    private function isValidMonth($month)
    {
        return preg_match('/^\d{4}-\d{2}$/', $month)
            && Carbon::createFromFormat('Y-m', $month)->format('Y-m') === $month;
    }
}
