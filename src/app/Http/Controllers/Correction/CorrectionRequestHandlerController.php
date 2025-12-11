<?php

namespace App\Http\Controllers\Correction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// ユーザー側コントローラ
use App\Http\Controllers\AttendanceCorrectionRequestController as UserController;

// 管理者側コントローラ
use App\Http\Controllers\Admin\AttendanceCorrectionRequestController as AdminController;

class CorrectionRequestHandlerController extends Controller
{
    public function handleList(Request $request)
    {
        if ($request->correction_role === 'admin') {
            return app(AdminController::class)->index($request);
        }

        return app(UserController::class)->index($request);
    }

    public function handleStore(Request $request)
    {
        if ($request->correction_role === 'admin') {
            return app(AdminController::class)->store($request);
        }

        return app(UserController::class)->store($request);
    }

    public function handleDetail(Request $request, $id)
    {
        if ($request->correction_role === 'admin') {
            return app(AdminController::class)->show($id);
        }

        return app(UserController::class)->show($id);
    }
}
