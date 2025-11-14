<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * 今日の打刻画面表示
     *
     * 状態:
     * - before_work: 出勤前
     * - working: 勤務中
     * - on_break: 休憩中
     * - after_work: 退勤済
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $breakTime = null;
        $status = 'before_work';

        if ($attendance) {
            $breakTime = BreakTime::where('attendance_id', $attendance->id)
                ->latest('id')
                ->first();

            if (!$attendance->clock_out) {
                $status = ($breakTime && $breakTime->break_start && !$breakTime->break_end)
                    ? 'on_break'
                    : 'working';
            } else {
                $status = 'after_work';
            }
        }

        $attendances = $attendance ? collect([$attendance]) : collect([]);
        // Bladeで月ナビゲーションに使うために$monthを追加
        $month = Carbon::now()->format('Y-m');

        return view('user.attendance.index', compact('attendance', 'breakTime', 'status', 'month', 'attendances'));
    }

    /**
     * 打刻登録処理（出勤／退勤／休憩開始／休憩終了）
     */
    public function store(Request $request)
    {

        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        DB::beginTransaction();

        try {
            $attendance = Attendance::where('user_id', $user->id)
                ->where('work_date', $today)
                ->first();

            // 出勤打刻
            if ($request->has('clock_in')) {
                if ($attendance) {
                    return back()->with('error', '本日はすでに出勤済みです。');
                }

                Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $today,
                    'clock_in' => Carbon::now()->format('H:i:s'),
                ]);

                DB::commit();
                return back()->with('success', '出勤打刻しました。');
            }

            // 休憩開始
            if ($request->has('break_start')) {
                if (!$attendance) return back()->with('error', '出勤打刻が必要です。');
                if ($attendance->clock_out) return back()->with('error', '退勤後は休憩できません。');

                $latestBreak = BreakTime::where('attendance_id', $attendance->id)
                    ->latest('id')
                    ->first();

                if ($latestBreak && !$latestBreak->break_end) {
                    return back()->with('error', '現在休憩中です。');
                }

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => Carbon::now()->format('H:i:s'),
                ]);

                DB::commit();
                return back()->with('success', '休憩を開始しました。');
            }

            // 休憩終了
            if ($request->has('break_end')) {
                if (!$attendance) return back()->with('error', '出勤打刻が必要です。');
                if ($attendance->clock_out) return back()->with('error', '退勤後は休憩を終了できません。');

                $breakTime = BreakTime::where('attendance_id', $attendance->id)
                    ->whereNull('break_end')
                    ->latest('id')
                    ->first();

                if (!$breakTime) return back()->with('error', '休憩開始が未登録です。');

                $breakEnd = Carbon::now();
                $breakStart = Carbon::createFromFormat('H:i:s', $breakTime->break_start);
                $breakMinutes = $breakStart->diffInMinutes($breakEnd);

                $breakTime->update([
                    'break_end' => $breakEnd->format('H:i:s'),
                    'total_break_time' => $breakMinutes,
                ]);

                DB::commit();
                return back()->with('success', '休憩を終了しました。');
            }

            // 退勤打刻
            if ($request->has('clock_out')) {
                if (!$attendance) return back()->with('error', '出勤打刻が必要です。');
                if ($attendance->clock_out) return back()->with('error', '本日はすでに退勤済みです。');

                $clockOut = Carbon::now();
                $clockIn = Carbon::createFromFormat('H:i:s', $attendance->clock_in);
                $totalWorkMinutes = $clockIn->diffInMinutes($clockOut);

                $totalBreak = BreakTime::where('attendance_id', $attendance->id)
                    ->sum('total_break_time');

                $attendance->update([
                    'clock_out' => $clockOut->format('H:i:s'),
                    'total_work_time' => max($totalWorkMinutes - $totalBreak, 0),
                ]);

                DB::commit();
                return back()->with('success', '退勤打刻しました。');
            }

            DB::rollBack();
            return back()->with('error', '打刻処理が認識されませんでした。');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '打刻処理中にエラーが発生しました。');
        }
    }

    /**
     * 月単位勤怠一覧
     */
    public function list(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        $attendances = Attendance::where('user_id', $user->id)
            ->where('work_date', 'like', $month . '%')
            ->orderBy('work_date', 'desc')
            ->get();

        return view('user.attendance.list', compact('attendances', 'month'));
    }

    /**
     * 勤怠詳細
     */
    public function show($id)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        if ($attendance->user_id !== Auth::id()) {
            abort(403, 'このページにはアクセスできません。');
        }

        return view('user.attendance.detail', compact('attendance'));
    }
}
