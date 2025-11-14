<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH 勤怠管理</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header>
        <div class="logo">
            <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" style="height: 32px;">
        </div>
        <nav>
            <a href="#">勤怠</a>
            <a href="#">勤怠一覧</a>
            <a href="#">申請</a>
            <a href="#">ログアウト</a>
        </nav>
    </header>

    <main class="container">
        @yield('content')
    </main>
</body>
</html>
