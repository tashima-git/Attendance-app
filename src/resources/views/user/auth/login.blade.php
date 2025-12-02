@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">

<div class="login-container">
    <h1>ログイン</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autofocus novalidate>
            @error('email')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password" novalidate>
            @error('password')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        @if(session('error'))
            <p class="error-message">{{ session('error') }}</p>
        @endif

        <button type="submit" class="btn-submit">ログイン</button>

        <div class="login-link">
            <a href="{{ route('register') }}">会員登録はこちら</a>
        </div>
    </form>
</div>
@endsection
