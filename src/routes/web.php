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

/*
|--------------------------------------------------------------------------
| トップページ
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('attendance.index')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| メール認証
|--------------------------------------------------------------------------
*/
Route::get('/home', fn() => redirect()->route('verification.notice'));

Route::get('/email/verify', fn() => view('user.auth.verify-email'))
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendance.index')->with('message', 'メール認証が完了しました。');
})
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました。');
})
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

/*
|--------------------------------------------------------------------------
| 一般ユーザー向け（PG01〜PG06）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.show');

    // 修正申請関連（ユーザー）
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class, 'index'])
        ->name('correction_request.index');

    Route::post('/stamp_correction_request/store', [AttendanceCorrectionRequestController::class, 'store'])
        ->name('correction_request.store');

    Route::get('/stamp_correction_request/detail/{id}', [AttendanceCorrectionRequestController::class, 'show'])
        ->name('correction_request.show');
});

/*
|--------------------------------------------------------------------------
| 管理者向け（PG07～PG13）
|--------------------------------------------------------------------------
*/

// 管理者ログイン
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');


// prefix admin
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {

    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.list');

    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.show');

    Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    Route::get('/staff/list', [AdminStaffController::class, 'index'])
        ->name('admin.staff_list');

    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendances'])
        ->name('admin.attendance.staff');

    // 管理者 修正申請一覧（admin/ 配下版）
    Route::get('/correction_request/list', [AdminAttendanceCorrectionRequestController::class, 'index'])
        ->name('admin.correction_request.index');
});

/*
|--------------------------------------------------------------------------
| 管理者版：同じパス（/stamp_correction_request/list）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])
    ->get('/stamp_correction_request/list', [AdminAttendanceCorrectionRequestController::class, 'index'])
    ->name('admin.correction_request.list');

/*
|--------------------------------------------------------------------------
| 管理者版：修正申請詳細（※これが不足していた）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])
    ->get('/stamp_correction_request/detail/{id}', [AdminAttendanceCorrectionRequestController::class, 'show'])
    ->name('admin.correction_request.show');

/*
|--------------------------------------------------------------------------
| 管理者：承認処理
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])
    ->put('/stamp_correction_request/approve/{attendance_correction_request_id}',
        [AdminAttendanceCorrectionRequestController::class, 'approve'])
    ->name('admin.correction_request.approve');
