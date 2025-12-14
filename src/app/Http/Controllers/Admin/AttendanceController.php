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
public function show(Request $request, $id)
{
    if (!Auth::guard('admin')->check()) {
        return redirect()->route('admin.login');
    }

    // Blade から user_id と work_date を受け取る場合
    $userId    = $request->query('user_id');
    $workDate  = $request->query('work_date');

    if ($id > 0) {
        // 既存IDがある場合
        $attendance = Attendance::with(['user', 'breakTimes'])->find($id);
    } else {
        // 新規作成も可能
        if (!$userId || !$workDate) {
            abort(404, '対象ユーザーまたは日付が指定されていません。');
        }

        $attendance = Attendance::with('breakTimes')->firstOrCreate(
            [
                'user_id'   => $userId,
                'work_date' => $workDate,
            ],
            [
                'clock_in'  => null,
                'clock_out' => null,
                'remarks'   => '',
            ]
        );
    }

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

    return view('admin.attendance.staff_monthly', [
        'user' => $user,
        'staff' => $user,
        'month' => $month,
        'start' => $start,
        'end' => $end,
        'attendanceMap' => $attendanceMap,
    ]);
    }

    /**
     * 管理者：勤怠更新（編集）
     */
public function update(AttendanceAdminUpdateRequest $request, $id)
{
$validated = $request->validated();

// Attendance を取得（存在しなければ作成）
$attendance = Attendance::with('breakTimes')->find($id);
if (!$attendance) {
    $attendance = Attendance::firstOrCreate(
        ['user_id' => $request->user_id, 'work_date' => $request->work_date],
        [
            'clock_in'  => $request->clock_in ? $request->work_date . ' ' . $request->clock_in : null,
            'clock_out' => $request->clock_out ? $request->work_date . ' ' . $request->clock_out : null,
            'remarks'   => $request->remarks,
        ]
    );
} else {
    // 既存勤怠の更新
    $attendance->clock_in  = $request->filled('clock_in')  ? $attendance->work_date . ' ' . $request->clock_in : null;
    $attendance->clock_out = $request->filled('clock_out') ? $attendance->work_date . ' ' . $request->clock_out : null;
    $attendance->remarks   = $request->remarks;
    $attendance->save();

    // 既存休憩削除
    $attendance->breakTimes()->delete();
}

// 休憩時間追加（新規も更新も共通）
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

public function exportCsv(Request $request, $id)
{
    $month = $request->input('month', now()->format('Y-m'));

    $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    $end   = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();

    // 勤怠データ取得（休憩データもロード）
    $attendances = Attendance::with('breakTimes')
        ->where('user_id', $id)
        ->whereBetween('work_date', [$start, $end])
        ->orderBy('work_date')
        ->get();

    // Times を Blade と同じ処理で計算
    $attendances = $attendances->map(fn($a) => $this->calculateTimes($a));

    // CSV ヘッダ
    $csvHeader = ['日付', '出勤', '退勤', '休憩合計', '労働時間'];

    $csvData = [];

    foreach ($attendances as $att) {
        $csvData[] = [
            $att->work_date,
            $att->clock_in  ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '',
            $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '',
            $att->break_hm,          // ← コントローラ計算値を使う（正しい）
            $att->total_work_time_hm // ← コントローラ計算値を使う（正しい）
        ];
    }

    $filename = "staff_{$id}_{$month}.csv";

    $callback = function() use ($csvHeader, $csvData) {
        $stream = fopen('php://output', 'w');
        fprintf($stream, chr(0xEF).chr(0xBB).chr(0xBF)); // Excel対策

        fputcsv($stream, $csvHeader);

        foreach ($csvData as $line) {
            fputcsv($stream, $line);
        }

        fclose($stream);
    };

    return response()->streamDownload($callback, $filename, [
        "Content-Type" => "text/csv",
    ]);
}

}
