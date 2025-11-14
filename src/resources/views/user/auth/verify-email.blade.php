@extends('layouts.app')

@section('title', 'メール認証')

@section('content')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">

<main class="verify-main">
    <div class="verify-content">

        <p class="verify-message">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        {{-- Mailtrapの確認用リンク --}}
        <a href="http://localhost:8025/" target="_blank" class="verify-button">
            認証はこちらから
        </a>

        {{-- 認証メール再送 --}}
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="resend-link">
                認証メールを再送する
            </button>
        </form>

        @if(session('message'))
            <p class="verify-success-message">{{ session('message') }}</p>
        @endif
    </div>
</main>
@endsection
