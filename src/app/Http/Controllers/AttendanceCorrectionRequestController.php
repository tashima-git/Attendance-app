<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Http\Requests\AttendanceCorrectionRequestRequest;

class AttendanceCorrectionRequestController extends Controller
{
    /**
     * 申請一覧を表示（承認待ち・承認済みの切り替え対応）
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // デフォルトは「承認待ち」
        $status = $request->get('status', 'pending');

        // ログイン中ユーザーの申請のみ取得
        $requests = AttendanceCorrectionRequest::where('user_id', Auth::id())
            ->where('status', $status)
            ->with('attendance')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('user.correction_request_list', compact('requests', 'status'));
    }

    /**
     * 申請詳細画面の表示
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $correctionRequest = AttendanceCorrectionRequest::where('user_id', Auth::id())
            ->with('attendance')
            ->findOrFail($id);

        return view('user.correction_request_detail', compact('correctionRequest'));
    }

    /**
     * 勤怠修正申請の登録処理
     *
     * @param AttendanceCorrectionRequestRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AttendanceCorrectionRequestRequest $request)
    {
        $attendance = Attendance::where('id', $request->attendance_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // 新しい修正申請を登録
        AttendanceCorrectionRequest::create([
            'user_id' => Auth::id(),
            'attendance_id' => $attendance->id,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'break_start' => $request->break_start,
            'break_end' => $request->break_end,
            'reason' => $request->reason,
            'status' => 'pending', // 登録時は承認待ち固定
        ]);

        return redirect()
            ->route('attendance_corrections.index', ['status' => 'pending'])
            ->with('success', '勤怠修正申請を提出しました。');
    }
}
