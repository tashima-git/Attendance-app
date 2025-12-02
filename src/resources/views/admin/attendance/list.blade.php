@extends('layouts.admin')

@section('title', '勤怠一覧（管理者）')

@section('content')

<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">

@php
    $currentDate = $date ?? \Carbon\Carbon::today()->format('Y-m-d');
    $carbonDate  = \Carbon\Carbon::parse($currentDate);
@endphp

<h1>{{ $carbonDate->format('Y年n月j日') }}の勤怠</h1>

<!-- 日付ナビゲーション -->
<div class="date-nav">

    <!-- 前日 -->
    <form method="GET" action="{{ route('admin.attendance.list') }}">
        <input type="hidden" name="date" value="{{ $carbonDate->copy()->subDay()->format('Y-m-d') }}">
        <button type="submit" class="nav-button">← 前日</button>
    </form>

    <!-- 日付表示 & カレンダー -->
    <div class="month-form">
        <form method="GET" action="{{ route('admin.attendance.list') }}" class="date-form">
            <label for="date-input" class="month-display-text date-picker-label" data-target="date-input" data-form="dateForm">
                {{ $carbonDate->format('Y/m/d') }}
            </label>

            <input
                type="date"
                name="date"
                id="date-input"
                class="month-picker-hidden"
                value="{{ $currentDate }}"
            >
        </form>
    </div>

    <!-- 翌日 -->
    <form method="GET" action="{{ route('admin.attendance.list') }}">
        <input type="hidden" name="date" value="{{ $carbonDate->copy()->addDay()->format('Y-m-d') }}">
        <button type="submit" class="nav-button">翌日 →</button>
    </form>

</div>

<!-- 勤怠テーブル -->
<table class="attendance-table">
    <thead>
        <tr>
            <th class="name-cell">スタッフ名</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>
    </thead>

    <tbody>

        @forelse ($attendances as $attendance)
            @php
                $in  = $attendance->clock_in  ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '';
                $out = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '';

                // 休憩・実働を H:i 形式へ
                $breakMinutes = $attendance->break_total;
                $totalMinutes = $attendance->real_work_total;

                $break = $breakMinutes !== null
                    ? sprintf('%02d:%02d', intdiv($breakMinutes,60), $breakMinutes % 60)
                    : '';

                $total = $totalMinutes !== null
                    ? sprintf('%02d:%02d', intdiv($totalMinutes,60), $totalMinutes % 60)
                    : '';
            @endphp

            <tr>
                <td class="name-cell">{{ $attendance->user->name }}</td>
                <td>{{ $in }}</td>
                <td>{{ $out }}</td>
                <td>{{ $break }}</td>
                <td>{{ $total }}</td>
                <td>
                    <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}"
                       class="status status-calculated">
                        詳細
                    </a>
                </td>
            </tr>

        @empty
            <tr>
                <td colspan="6" class="no-data">
                    この日に勤怠データがあるスタッフはいません
                </td>
            </tr>
        @endforelse

    </tbody>
</table>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const label  = document.querySelector(".date-picker-label");
    const picker = document.getElementById("date-input");
    const form   = document.querySelector(".date-form");

    if(label && picker && picker.showPicker){
        label.addEventListener("click", ()=> picker.showPicker());
    }

    if(picker && form){
        picker.addEventListener("change", ()=> form.submit());
    }
});
</script>

@endsection
