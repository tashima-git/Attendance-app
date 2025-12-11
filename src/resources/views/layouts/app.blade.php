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

@auth
    @php
        $user = auth()->user();
    @endphp

    <nav>
        <a href="{{ route('attendance.index') }}">勤怠</a>
        <a href="{{ route('attendance.list') }}">勤怠一覧</a>

        {{-- ユーザーがメール認証済みなら修正申請リンク --}}
@if(auth()->guard('web')->check() && auth()->user()->hasVerifiedEmail())
    <a href="{{ url('/stamp_correction_request/list') }}">申請</a>
@endif




        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="logout">ログアウト</button>
        </form>
    </nav>
@endauth


</header>





    <main class="container">
        @yield('content')
    </main>
</body>
</html>
