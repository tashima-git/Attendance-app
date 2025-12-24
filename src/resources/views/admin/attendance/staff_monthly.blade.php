@extends('layouts.admin')

@section('title', $user->name . ' の勤怠一覧')

@section('content')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">

<div class="container">
    <h1>{{ $user->name }} さんの勤怠一覧</h1>

    @php
        $currentMonth = $month;
        $carbonMonth = \Carbon\Carbon::parse($currentMonth);
    @endphp

    <div class="date-nav">
        <form method="GET" action="{{ route('admin.attendance.staff', ['id' => $user->id]) }}">
            <input type="hidden" name="month" value="{{ $carbonMonth->copy()->subMonth()->format('Y-m') }}">
            <button type="submit" class="nav-button">← 前月</button>
        </form>

        <div class="month-form">
            <form method="GET"
                  action="{{ route('admin.attendance.staff', ['id' => $user->id]) }}"
                  id="monthForm">
                <label for="month-input" id="month-display" class="month-display-text">
                    {{ $carbonMonth->format('Y/m') }}
                </label>

                <input type="month"
                       name="month"
                       id="month-input"
                       class="month-picker-hidden"
                       value="{{ $currentMonth }}">
            </form>
        </div>

        <form method="GET" action="{{ route('admin.attendance.staff', ['id' => $user->id]) }}">
            <input type="hidden" name="month" value="{{ $carbonMonth->copy()->addMonth()->format('Y-m') }}">
            <button type="submit" class="nav-button">翌月 →</button>
        </form>
    </div>

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
            @php
                $week = ['日','月','火','水','木','金','土'];
                $loopDate = $start->copy();
            @endphp

            @while ($loopDate <= $end)
                @php
                    $dayStr = $loopDate->format('Y-m-d');
                    $attendance = $attendanceMap[$dayStr] ?? null;
                    $w = $week[$loopDate->dayOfWeek];

                    $in = $attendance && $attendance->clock_in
                        ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                        : '';

                    $out = $attendance && $attendance->clock_out
                        ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                        : '';

                    $break = $attendance ? $attendance->break_hm : '';
                    $total = $attendance ? $attendance->total_work_time_hm : '';
                @endphp

                <tr>
                    <td class="date-cell">
                        {{ $loopDate->format('m/d') }}({{ $w }})
                    </td>
                    <td>{{ $in }}</td>
                    <td>{{ $out }}</td>
                    <td>{{ $attendance && $break && $break !== '00:00' ? $break : '' }}</td>
                    <td>{{ $attendance && $total && $total !== '00:00' ? $total : '' }}</td>
                    <td>
                        <a href="{{ url("/admin/attendance/{$user->id}?date={$loopDate->format('Y-m-d')}") }}"
                           class="status status-calculated">
                            詳細
                        </a>
                    </td>
                </tr>

                @php $loopDate->addDay(); @endphp
            @endwhile
        </tbody>
    </table>

    <div class="btn">
        <form method="GET" action="{{ route('admin.attendance.staff.csv', ['id' => $staff->id]) }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <button type="submit" class="btn-csv">CSVダウンロード</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const label  = document.getElementById("month-display");
    const picker = document.getElementById("month-input");
    const form   = document.getElementById("monthForm");

    if (label && picker && picker.showPicker) {
        label.addEventListener("click", () => picker.showPicker());
    }

    if (picker && form) {
        picker.addEventListener("change", () => form.submit());
    }
});
</script>
@endsection
