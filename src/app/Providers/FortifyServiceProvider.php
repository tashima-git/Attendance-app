<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\LoginViewResponse;
use Illuminate\Http\Request;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);
    }

    public function boot(): void
    {
        // ユーザー登録画面
        Fortify::registerView(function () {
            return view('user.auth.register');
        });

        // ログイン画面切り替え
        Fortify::loginView(function (Request $request) {
            if ($request->is('admin/login')) {
                return view('admin.auth.login');
            }
            return view('user.auth.login');
        });

        // 認証処理
        Fortify::authenticateUsing(function (Request $request) {
            if ($request->is('admin/login')) {
                $admin = Admin::where('email', $request->email)->first();
                if ($admin && Hash::check($request->password, $admin->password)) {
                    return $admin;
                }
            } else {
                $user = User::where('email', $request->email)->first();
                if ($user && Hash::check($request->password, $user->password)) {
                    return $user;
                }
            }
            return null;
        });

        // LoginViewResponse
        $this->app->singleton(LoginViewResponse::class, function () {
            return new class implements LoginViewResponse {
                public function toResponse($request)
                {
                    $view = $request->is('admin/login')
                        ? 'admin.auth.login'
                        : 'user.auth.login';

                    return view($view);
                }
            };
        });

        // メール認証ビュー
        Fortify::verifyEmailView(function () {
            return view('user.auth.verify-email');
        });

    }
}
