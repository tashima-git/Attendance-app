@extends('layouts.app')

@section('title', '勤怠一覧')

@section('content')

<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">

<div class="container">
    <h1>勤怠一覧</h1>

    {{-- 月ナビゲーション --}}
    <div class="date-nav">
        @php
            // $month が存在しない場合は今月をデフォルト
            $currentMonth = isset($month) ? $month : \Carbon\Carbon::now()->format('Y-m');
        @endphp

        <form method="GET" action="{{ route('attendance.list') }}">
            <input type="hidden" name="month" value="{{ \Carbon\Carbon::parse($currentMonth)->subMonth()->format('Y-m') }}">
            <button type="submit">← 前月</button>
        </form>

        <div class="current-date">{{ \Carbon\Carbon::parse($currentMonth)->format('Y/m') }}</div>

        <form method="GET" action="{{ route('attendance.list') }}">
            <input type="hidden" name="month" value="{{ \Carbon\Carbon::parse($currentMonth)->addMonth()->format('Y-m') }}">
            <button type="submit">翌月 →</button>
        </form>
    </div>

    {{-- 勤怠テーブル --}}
    <table>
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
                <tr>
                    <td class="date-cell">{{ \Carbon\Carbon::parse($attendance->work_date)->format('m/d(D)') }}</td>
                    <td>{{ $attendance->clock_in ?? '' }}</td>
                    <td>{{ $attendance->clock_out ?? '' }}</td>
                    <td>{{ $attendance->total_break_time ? gmdate('H:i', $attendance->total_break_time * 60) : '' }}</td>
                    <td>{{ $attendance->total_work_time ? gmdate('H:i', $attendance->total_work_time * 60) : '' }}</td>
                    <td>
                        <a href="{{ route('attendance.show', $attendance->id) }}" class="status status-calculated">詳細</a>
                    </td>
                </tr>
            @endforeach

            {{-- 該当月にデータがない場合 --}}
            @if($attendances->isEmpty())
                <tr>
                    <td colspan="6">勤怠データがありません</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
@endsection
