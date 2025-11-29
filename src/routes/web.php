<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\AttendanceCorrectionRequestController as AdminAttendanceCorrectionRequestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

// ===================================================
// トップページ
// ===================================================
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('attendance.index'); // ログイン済みなら勤怠画面
    }
    return redirect()->route('login'); // 未ログインならログインページ
});

// ===================================================
// メール認証ルート（Fortify対応）
// ===================================================

// /home にアクセスされた場合はメール認証画面にリダイレクト
Route::get('/home', function () {
    return redirect()->route('verification.notice');
});


// 認証待ち画面
Route::get('/email/verify', function () {
    return view('user.auth.verify-email'); // verify-email.blade.php
})->middleware('auth')->name('verification.notice');

// メール内リンククリック後の処理
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); // 認証完了
    return redirect()->route('attendance.index')->with('message', 'メール認証が完了しました。');
})->middleware(['auth', 'signed'])->name('verification.verify');

// 認証メール再送
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// ===================================================
// 一般ユーザー向けルート（ログイン＋メール認証必須）
// ===================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // 出勤登録画面
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // 勤怠詳細
    Route::get('/attendance/detail/{date}', [AttendanceController::class, 'show'])->name('attendance.show');

    // 勤怠修正申請
    Route::post('/stamp_correction_request/store', [AttendanceCorrectionRequestController::class, 'store'])
        ->name('correction_request.store');

    // 修正申請一覧
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class, 'index'])
        ->name('correction_request.index');

    // 修正申請詳細
    Route::get('/stamp_correction_request/detail/{id}', [AttendanceCorrectionRequestController::class, 'show'])
        ->name('correction_request.show');
});

// ===================================================
// 管理者向けルート
// ===================================================

// 管理者ログイン
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// 管理者専用エリア（auth:admin ミドルウェア）
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {

    // 勤怠一覧（全社員）
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.list');

    // 勤怠詳細（特定社員）
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');

    // スタッフ一覧
    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('admin.staff.list');

    // 特定スタッフの勤怠履歴
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendances'])
        ->name('admin.attendance.staff');

    // 勤怠修正申請一覧（全ユーザー）
    Route::get('/stamp_correction_request/list', [AdminAttendanceCorrectionRequestController::class, 'index'])
        ->name('admin.correction_request.list');

    // 修正申請承認
    Route::put('/stamp_correction_request/approve/{attendance_correction_request_id}', [AdminAttendanceCorrectionRequestController::class, 'approve'])
        ->name('admin.correction_request.approve');
});
