@extends('layouts.app')

@section('title', '勤怠打刻')

@section('content')

<link rel="stylesheet" href="{{ asset('css/attendance-index.css') }}">

<main>
    {{-- 勤務状態バッジ --}}
    <div class="status-badge">
        @switch($status)
            @case('before_work')
                勤務外
                @break
            @case('working')
                出勤中
                @break
            @case('on_break')
                休憩中
                @break
            @case('after_work')
                退勤済
                @break
        @endswitch
    </div>

    {{-- 今日の日付 --}}
    @php
        $weekdays = ['日','月','火','水','木','金','土'];
        $todayWeek = $weekdays[\Carbon\Carbon::today()->dayOfWeek];
    @endphp
    <div class="date">{{ \Carbon\Carbon::today()->format('Y年n月j日') }}({{ $todayWeek }})</div>

    {{-- 現在時刻（ページ読み込み時点） --}}
    <div class="time">{{ \Carbon\Carbon::now()->format('H:i') }}</div>

    {{-- 打刻ボタン群 --}}
    <form method="POST" action="{{ route('attendance.store') }}">
        @csrf

        <div class="button-group">
            @if($status === 'before_work')
                <button type="submit" name="clock_in" class="btn btn-primary">出勤</button>
            @endif

            @if($status === 'working')
                <button type="submit" name="clock_out" class="btn btn-primary">退勤</button>
                <button type="submit" name="break_start" class="btn btn-secondary">休憩入</button>
            @endif

            @if($status === 'on_break')
                <button type="submit" name="break_end" class="btn btn-secondary">休憩戻</button>
            @endif
        </div>

        @if($status === 'after_work')
            <div class="after-work">お疲れ様でした。</div>
        @endif
    </form>
</main>
@endsection
