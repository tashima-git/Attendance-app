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
     * 今日の打刻画面
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
        $month = Carbon::now()->format('Y-m');

        return view('user.attendance.index', compact('attendance', 'breakTime', 'status', 'month', 'attendances'));
    }

    /**
     * 出勤・退勤・休憩開始・休憩終了
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

            /**
             * 出勤
             */
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

            /**
             * 休憩開始
             */
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

            /**
             * 休憩終了
             */
            if ($request->has('break_end')) {
                if (!$attendance) return back()->with('error', '出勤打刻が必要です。');
                if ($attendance->clock_out) return back()->with('error', '退勤後は休憩終了できません。');

                $bt = BreakTime::where('attendance_id', $attendance->id)
                    ->whereNull('break_end')
                    ->latest('id')
                    ->first();

                if (!$bt) return back()->with('error', '休憩開始が未登録です。');

                $breakEnd = Carbon::now();
                $breakStart = Carbon::parse($bt->break_start); // 安全な parse に変更
                $minutes = $breakStart->diffInMinutes($breakEnd);

                $bt->update([
                    'break_end' => $breakEnd->format('H:i:s'),
                    'total_break_time' => $minutes,
                ]);

                DB::commit();
                return back()->with('success', '休憩を終了しました。');
            }

            /**
             * 退勤
             */
            if ($request->has('clock_out')) {
                if (!$attendance) return back()->with('error', '出勤打刻が必要です。');
                if ($attendance->clock_out) return back()->with('error', '本日はすでに退勤済みです。');

                $clockOut = Carbon::now();
                $clockIn = Carbon::parse($attendance->clock_in);

                $totalWorkMinutes = $clockIn->diffInMinutes($clockOut);
                $totalBreakMinutes = BreakTime::where('attendance_id', $attendance->id)
                    ->sum('total_break_time');

                $attendance->update([
                    // 🔥 秒ありで統一（重要修正）
                    'clock_out' => $clockOut->format('H:i:s'),
                    'total_work_time' => max($totalWorkMinutes - $totalBreakMinutes, 0),
                ]);

                DB::commit();
                return back()->with('success', '退勤打刻しました。');
            }

            DB::rollBack();
            return back()->with('error', '処理を認識できませんでした。');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'エラーが発生しました。');
        }
    }

    /**
     * 月次勤怠一覧（HH:MM 表示・休憩 HH:MM 表示）
     */
    public function list(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date')
            ->get();

        // 日付キー化 + 勤務時間・休憩時間計算
        $attendanceMap = $attendances->mapWithKeys(function ($attendance) {
            $dateKey = Carbon::parse($attendance->work_date)->format('Y-m-d');

            // 勤務時間（分）
            $totalMinutes = 0;
            if ($attendance->clock_in && $attendance->clock_out) {
                $in  = Carbon::parse($attendance->clock_in);
                $out = Carbon::parse($attendance->clock_out);
                $totalMinutes = $in->diffInMinutes($out)
                                - $attendance->breakTimes->sum('total_break_time');
                $totalMinutes = max($totalMinutes, 0);
            }

            // HH:MM に変換
            $attendance->setAttribute(
                'total_work_time_hm',
                sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60)
            );

            // 休憩時間
            $breakMinutes = $attendance->breakTimes->sum('total_break_time');
            $attendance->setAttribute(
                'break_hm',
                sprintf('%02d:%02d', floor($breakMinutes / 60), $breakMinutes % 60)
            );

            return [$dateKey => $attendance];
        });

        return view('user.attendance.list', compact('attendanceMap', 'month', 'start', 'end'));
    }

    /**
     * 勤怠詳細（勤怠が無い日でも安全に表示）
     */
    public function show($date)
    {
        $user = Auth::user();

        $attendance = Attendance::with(['breakTimes', 'user', 'correctionRequests'])
            ->where('user_id', $user->id)
            ->where('work_date', $date)
            ->first();

        // 勤怠なし → 空の Attendance を作る
        if (!$attendance) {
            $attendance = new Attendance();
            $attendance->id = null;
            $attendance->user_id = $user->id;
            $attendance->work_date = $date;
            $attendance->clock_in = null;
            $attendance->clock_out = null;

            // 空リレーション
            $attendance->setRelation('breakTimes', collect());
            $attendance->setRelation('correctionRequests', collect());
            $attendance->setRelation('user', $user);
        }

        return view('user.attendance.detail', compact('attendance', 'date'));
    }
}
