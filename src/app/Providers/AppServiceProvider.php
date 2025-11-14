<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ログインのレートリミッター
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->email ?? $request->ip());
        });

        // 日本語ロケール
        setlocale(LC_TIME, 'ja_JP.UTF-8');

        // ヘルパー読み込み
        require_once app_path('Helpers/AttendanceHelper.php');
    }
}

