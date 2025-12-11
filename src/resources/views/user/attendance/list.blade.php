@extends('layouts.app')

@section('title', '勤怠一覧')

@section('content')

<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">

<div class="container">
    <h1>勤怠一覧</h1>

    @php
        $currentMonth = $month ?? \Carbon\Carbon::now()->format('Y-m');
        $carbonMonth = \Carbon\Carbon::parse($currentMonth);
    @endphp

    <!-- 月ナビゲーション -->
    <div class="date-nav">

        <!-- 前月 -->
        <form method="GET" action="{{ route('attendance.list') }}">
            <input type="hidden" name="month" value="{{ $carbonMonth->copy()->subMonth()->format('Y-m') }}">
            <button type="submit" class="nav-button">← 前月</button>
        </form>

        <!-- 今月表示 & 月選択 -->
        <div class="month-form">
            <form method="GET" action="{{ route('attendance.list') }}" id="monthForm">
                <label for="month-input" id="month-display" class="month-display-text">
                    {{ $carbonMonth->format('Y/m') }}
                </label>

                <input
                    type="month"
                    name="month"
                    id="month-input"
                    class="month-picker-hidden"
                    value="{{ $currentMonth }}"
                >
            </form>
        </div>

        <!-- 翌月 -->
        <form method="GET" action="{{ route('attendance.list') }}">
            <input type="hidden" name="month" value="{{ $carbonMonth->copy()->addMonth()->format('Y-m') }}">
            <button type="submit" class="nav-button">翌月 →</button>
        </form>
    </div>

    <!-- 勤怠テーブル -->
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

                // 出勤・退勤
                $in  = $attendance && $attendance->clock_in
                        ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                        : '';

                $out = $attendance && $attendance->clock_out
                        ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                        : '';

                // 休憩時間 HH:MM
                $break = $attendance ? $attendance->break_hm : '';

                // 合計労働時間 HH:MM
                $total = $attendance ? $attendance->total_work_time_hm : '';
            @endphp

            <tr>
                <td class="date-cell">{{ $loopDate->format('m/d') }}({{ $w }})</td>

                <td>{{ $in }}</td>
                <td>{{ $out }}</td>

                <td>{{ $attendance ? $break : '' }}</td>

                <td>{{ $attendance ? $total : '' }}</td>

                <td>
                    @php
    $att = $attendanceMap[$dayStr] ?? null;
@endphp

<a href="{{ route('attendance.show', $dayStr) }}" class="status status-calculated">
    詳細
</a>



                </td>
            </tr>

            @php
                $loopDate->addDay();
            @endphp

        @endwhile

        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const label  = document.getElementById("month-display");
    const picker = document.getElementById("month-input");
    const form   = document.getElementById("monthForm");

    // ラベルクリック → カレンダーを開く
    if (label && picker && picker.showPicker) {
        label.addEventListener("click", () => picker.showPicker());
    }

    // 月変更 → 自動送信
    if (picker && form) {
        picker.addEventListener("change", () => form.submit());
    }
});

</script>

@endsection
