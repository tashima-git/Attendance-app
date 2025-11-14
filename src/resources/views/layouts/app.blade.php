<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'COACHTECH 勤怠管理')</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header>
        <div class="logo">
            <a href="{{ auth()->check() ? route('attendance.index') : '#' }}">
                <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" style="height: 32px;">
            </a>
        </div>

        {{-- ログイン済みかつ認証済みユーザーのみナビ表示 --}}
        @auth
            @if(!request()->is('login') && !request()->is('register') && !request()->is('email/verify*'))
                <nav>
                    <a href="{{ route('attendance.index') }}">勤怠</a>
                    <a href="{{ route('attendance.list') }}">勤怠一覧</a>
                    <a href="{{ route('correction_request.index') }}">申請</a>
                    {{-- ログアウトフォーム --}}
                    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="logout">ログアウト</button>
                    </form>
                </nav>
            @endif
        @endauth
    </header>

    <main class="container">
        @yield('content')
    </main>
</body>
</html>
