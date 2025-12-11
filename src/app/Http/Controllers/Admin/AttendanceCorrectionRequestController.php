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
     * ▼ PG12：勤怠修正申請一覧（管理者）
     *   status=pending|approved でタブ切り替え
     */
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $status = $request->query('status', 'pending');

        $query = AttendanceCorrectionRequest::with(['user', 'attendance']);

        if ($status === 'pending') {
            $query->where('status', 'pending');
        } elseif ($status === 'approved') {
            $query->where('status', 'approved');
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.attendanceCorrectionRequest.list', [
            'requests' => $requests,
            'status'   => $status,
        ]);
    }

    /**
     * ▼ 修正申請の詳細画面（承認画面）
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $requestData = AttendanceCorrectionRequest::with([
            'user',
            'attendance',
            'correctionBreakTimes', // 休憩リレーションもロード
        ])->findOrFail($id);

        return view('admin.attendanceCorrectionRequest.approve', compact('requestData'));
    }

    /**
     * ▼ 修正申請を承認（PG13）
     */
    public function approve(Request $request, $id)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $requestData = AttendanceCorrectionRequest::with('correctionBreakTimes')->findOrFail($id);

        DB::beginTransaction();
        try {
            // 勤怠データ取得
            $attendance = $requestData->attendance;

            // 勤怠情報更新
            $attendance->update([
                'clock_in'  => $requestData->clock_in,
                'clock_out' => $requestData->clock_out,
                'remarks'   => $requestData->remarks,
            ]);

            // 休憩情報を申請内容で上書き
            $attendance->breakTimes()->delete(); // 既存休憩を削除
            foreach ($requestData->correctionBreakTimes as $break) {
                $attendance->breakTimes()->create([
                    'break_start' => $break->break_start,
                    'break_end'   => $break->break_end,
                ]);
            }

            // 申請ステータスを承認に更新
            $requestData->update([
                'status'      => 'approved',
                'approved_by' => Auth::guard('admin')->id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.correction_request.list', ['status' => 'pending'])
                ->with('success', '申請を承認しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '承認処理中にエラーが発生しました。');
        }
    }
}
