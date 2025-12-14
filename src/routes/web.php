<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;

/* 管理者 */
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\AttendanceCorrectionRequestController as AdminAttendanceCorrectionRequestController;
use App\Http\Controllers\Correction\CorrectionRequestHandlerController;

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
    return redirect()->route('attendance.index')
        ->with('message', 'メール認証が完了しました。');
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

    // 勤怠トップ
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])
        ->name('attendance.store');

    // 勤怠一覧（ユーザー）
    Route::get('/attendance/list', [AttendanceController::class, 'list'])
        ->name('attendance.list');

    // 勤怠詳細
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])
        ->name('attendance.show');
});


/*
|--------------------------------------------------------------------------
| 修正申請（ユーザー / 管理者 共通URL）
| ※ correction.route ミドルウェアで振り分け
|--------------------------------------------------------------------------
*/
// 修正申請（一般ユーザー / 管理者 共通）
Route::middleware(['auth:web', 'verified', 'correction.route'])
    ->group(function () {

        Route::get('/stamp_correction_request/list', 
            [AttendanceCorrectionRequestController::class, 'index']
        )->name('correction_request.index');

        Route::post('/stamp_correction_request/store', 
            [AttendanceCorrectionRequestController::class, 'store']
        )->name('correction_request.store');

        Route::get('/stamp_correction_request/detail/{id}', 
            [AttendanceCorrectionRequestController::class, 'show']
        )->name('correction_request.show');

    });



/*
|--------------------------------------------------------------------------
| 管理者ログイン（admin guard）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('web')->group(function () {

    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AdminLoginController::class, 'login'])
        ->name('login.submit');

    Route::post('/logout', [AdminLoginController::class, 'logout'])
        ->name('logout');
});


/*
|--------------------------------------------------------------------------
| 管理者向け（PG07〜PG13）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/attendance/list',
            [AdminAttendanceController::class, 'index']
        )->name('attendance.list');

        Route::get('/attendance/{id}',
            [AdminAttendanceController::class, 'show']
        )->name('attendance.show');

        Route::put('/attendance/{id}',
            [AdminAttendanceController::class, 'update']
        )->name('attendance.update');

        Route::get('/staff/list',
            [AdminStaffController::class, 'index']
        )->name('staff_list');

        Route::get('/attendance/staff/{id}',
            [AdminAttendanceController::class, 'staffAttendances']
        )->name('attendance.staff');

        Route::get('/attendance/staff/{id}/csv',
            [AdminAttendanceController::class, 'exportCsv']
        )->name('attendance.staff.csv');

        Route::get('/correction-request/list',
            [AdminAttendanceCorrectionRequestController::class, 'index']
        )->name('correction_request.list');

        Route::get('/correction-request/detail/{id}',
            [AdminAttendanceCorrectionRequestController::class, 'show']
        )->name('correction_request.show');

        Route::post('/correction-request/approve/{id}',
            [AdminAttendanceCorrectionRequestController::class, 'approve']
        )->name('correction_request.approve');
    });
