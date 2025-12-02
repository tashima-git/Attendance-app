<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\FailedLoginAttemptResponse;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\LoginViewResponse;
use Laravel\Fortify\Contracts\FailedLoginResponse;
use Laravel\Fortify\Http\Requests\RegisterRequest as FortifyRegisterRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 会員登録時のユーザー作成
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);

        // ログイン失敗メッセージを日本語に置き換え
        $this->app->singleton(
            FailedLoginResponse::class,
            FailedLoginAttemptResponse::class
        );
    }

    public function boot(): void
    {
        /**
         * =============================
         * 1. 会員登録画面
         * =============================
         */
        Fortify::registerView(fn() => view('user.auth.register'));

        /**
         * =============================
         * 2. ログイン画面（一般 / 管理者）
         * =============================
         */
        Fortify::loginView(function (Request $request) {
            return $request->is('admin/login')
                ? view('admin.auth.login')
                : view('user.auth.login');
        });

        /**
         * =============================
         * 3. 認証（一般 / 管理者）
         * =============================
         */
        Fortify::authenticateUsing(function (Request $request) {

            // 管理者ログイン処理
            if ($request->is('admin/login')) {
                $admin = Admin::where('email', $request->email)->first();
                if ($admin && Hash::check($request->password, $admin->password)) {
                    return $admin;
                }
                return null;
            }

            // 一般ユーザーログイン処理
            $user = User::where('email', $request->email)->first();
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }
            return null;
        });

        /**
         * =============================
         * 4. FormRequest を Fortify に適用
         * =============================
         */

        // Register Request 差し替え
        $this->app->resolving(FortifyRegisterRequest::class, function () {
            return app(RegisterRequest::class);
        });

        // Login Request 差し替え
        $this->app->resolving(FortifyLoginRequest::class, function () {
            return app(LoginRequest::class);
        });

        /**
         * =============================
         * 5. ログイン画面 response
         * =============================
         */
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

        /**
         * =============================
         * 6. メール認証画面
         * =============================
         */
        Fortify::verifyEmailView(fn() => view('user.auth.verify-email'));
        Fortify::redirects('/email/verify');

        /**
         * =============================
         * 7. 会員登録直後ログアウト
         * =============================
         */
        \Event::listen(Registered::class, function (Registered $event) {
            if ($event->user instanceof User) {
                Auth::logout();
            }
        });
    }
}
