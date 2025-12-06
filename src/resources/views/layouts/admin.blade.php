<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'COACHTECH 勤怠管理（管理者）')</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
    <header>
        <div class="logo">
            <a href="{{ Auth::guard('admin')->check() ? route('admin.attendance.list') : '#' }}">
                <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" style="height: 32px;">
            </a>
        </div>

@auth('admin')
    @if (!request()->is('admin/login'))
        <nav>
            <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
            <a href="{{ route('admin.staff_list') }}">スタッフ一覧</a>
            <a href="{{ route('admin.correction_request.list') }}">申請一覧</a>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
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
