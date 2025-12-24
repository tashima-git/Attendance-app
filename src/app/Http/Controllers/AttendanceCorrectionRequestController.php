<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\CorrectionBreakTime;
use App\Http\Requests\AttendanceCorrectionRequestRequest;
use Carbon\Carbon;

class AttendanceCorrectionRequestController extends Controller
{
    /**
     * 申請一覧（承認待ち・承認済み）
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $status = $request->get('status', 'pending');

        $requests = AttendanceCorrectionRequest::where('user_id', $user->id)
            ->where('status', $status)
            ->with(['attendance', 'correctionBreakTimes'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('user.attendanceCorrectionRequest.list', compact('requests', 'status'));
    }

    /**
     * 詳細画面
     */
    public function show($id)
    {
        $user = auth()->user();

        $correctionRequest = AttendanceCorrectionRequest::where('user_id', $user->id)
            ->with(['attendance', 'correctionBreakTimes'])
            ->findOrFail($id);

            $attendance = $correctionRequest->attendance;

        return view('user.attendance.detail', compact('correctionRequest', 'attendance'));
    }

    /**
     * 修正申請の新規登録
     */
    public function store(AttendanceCorrectionRequestRequest $request)
    {
        $user = auth()->user();

        $attendance = Attendance::where('id', $request->attendance_id)
            ->where('user_id', $user->id)
            ->first();

        $correctionRequest = AttendanceCorrectionRequest::create([
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id ?? null,
            'work_date'     => $request->work_date,
            'clock_in'      => $request->clock_in,
            'clock_out'     => $request->clock_out,
            'remarks'       => $request->remarks,
            'status'        => 'pending',
        ]);

        if ($request->filled('breaks')) {
            foreach ($request->breaks as $break) {
                if (!empty($break['break_start']) && !empty($break['break_end'])) {
                    CorrectionBreakTime::create([
                        'correction_request_id' => $correctionRequest->id,
                        'break_start'           => $break['break_start'],
                        'break_end'             => $break['break_end'],
                        'total_break_time'      => $this->calcBreakMinutes(
                            $break['break_start'],
                            $break['break_end']
                        ),
                    ]);
                }
            }
        }

        return redirect()
            ->route('correction_request.index', ['status' => 'pending'])
            ->with('success', '勤怠修正申請を提出しました。');
    }

    /**
     * 休憩時間の合計（分）
     */
    private function calcBreakMinutes(string $start, string $end): int
    {
        try {
            $startTime = Carbon::createFromFormat('H:i', $start);
            $endTime   = Carbon::createFromFormat('H:i', $end);
            return $startTime->diffInMinutes($endTime);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
