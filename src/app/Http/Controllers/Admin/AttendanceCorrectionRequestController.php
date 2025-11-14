<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionRequestController extends Controller
{
    /**
     * 修正申請一覧を表示（全ユーザー分）
     */
    public function index()
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $requests = AttendanceCorrectionRequest::with(['user', 'attendance'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.correction_request_list', compact('requests'));
    }

    /**
     * 修正申請詳細を表示（承認画面）
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $requestData = AttendanceCorrectionRequest::with(['user', 'attendance'])
            ->findOrFail($id);

        return view('admin.correction_request_approve', compact('requestData'));
    }

    /**
     * 修正申請を承認する
     */
    public function approve(Request $request, $id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $requestData = AttendanceCorrectionRequest::findOrFail($id);

        DB::beginTransaction();
        try {
            // 承認処理：勤怠データを更新
            $attendance = Attendance::findOrFail($requestData->attendance_id);
            $attendance->update([
                'clock_in'      => $requestData->clock_in,
                'break_start'   => $requestData->break_start,
                'break_end'     => $requestData->break_end,
                'clock_out'     => $requestData->clock_out,
            ]);

            $requestData->status = 'approved';
            $requestData->approved_by = Auth::guard('admin')->user()->id;
            $requestData->approved_at = now();
            $requestData->save();

            DB::commit();

            return redirect()->route('admin.correction_request.index')
                ->with('success', '申請を承認しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '承認処理中にエラーが発生しました。');
        }
    }
}
