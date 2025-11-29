@extends('layouts.app')

@section('title', '勤怠詳細')

@section('content')

<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">

@php
    use App\Models\AttendanceCorrectionRequest;

    // この日の申請（pending のみ）
    $pendingRequest = AttendanceCorrectionRequest::where('user_id', auth()->id())
        ->where('work_date', $attendance->work_date)
        ->where('status', 'pending')
        ->first();

    // 最新の申請（承認済み含む）
    $latestRequest = AttendanceCorrectionRequest::where('user_id', auth()->id())
        ->where('work_date', $attendance->work_date)
        ->latest()
        ->first();

    // 作業用フラグ
    $isPending = $pendingRequest !== null;

    // ① 出退勤・休憩・備考の表示元を決定
    // 申請 ＞ 勤怠 ＞ 空
    $source = $latestRequest ?? $attendance;
@endphp

<div class="container">
    <h1>勤怠詳細</h1>

    {{-- 申請中でない場合はフォームを表示 --}}
    @if (!$isPending)
        <form action="{{ route('correction_request.store') }}" method="POST">
            @csrf

            {{-- 勤怠ID（ない場合は空） --}}
            <input type="hidden" name="attendance_id" value="{{ $attendance->id ?? '' }}">

            {{-- 必ず work_date を送る --}}
            <input type="hidden" name="work_date" value="{{ $attendance->work_date }}">
    @endif


    <div class="detail-card">

        {{-- 名前 --}}
        <div class="detail-row">
            <div class="detail-label">名前</div>
            <div class="detail-value value-name">{{ $attendance->user->name }}</div>
        </div>

        {{-- 日付 --}}
        <div class="detail-row">
            <div class="detail-label">日付</div>
            <div class="detail-value value-day">
                <span class="year">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                <span class="month-day">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
            </div>
        </div>

        {{-- 出勤・退勤 --}}
        <div class="detail-row">
            <div class="detail-label">出勤・退勤</div>
            <div class="detail-value">
                <div class="time-row">

                    {{-- 出勤 --}}
                    <input type="text" class="time-input no-icon"
                        name="clock_in"
                        value="{{ $source->clock_in ? \Carbon\Carbon::createFromFormat('H:i:s', $source->clock_in)->format('H:i') : '' }}"
                        pattern="[0-2][0-9]:[0-5][0-9]"
                        maxlength="5"
                        {{ $isPending ? 'disabled' : '' }}>

                    <span class="time-separator">～</span>

                    {{-- 退勤 --}}
                    <input type="text" class="time-input no-icon"
                        name="clock_out"
                        value="{{ $source->clock_out ? \Carbon\Carbon::createFromFormat('H:i:s', $source->clock_out)->format('H:i') : '' }}"
                        pattern="[0-2][0-9]:[0-5][0-9]"
                        maxlength="5"
                        {{ $isPending ? 'disabled' : '' }}>

                </div>
            </div>
        </div>

        {{-- 休憩時間 --}}
        @php
            // 勤怠の休憩
            $attendanceBreaks = $attendance->breakTimes ?? collect();

            // 最新申請の休憩（未実装なら空配列扱い）
            $requestBreaks = ($latestRequest && method_exists($latestRequest, 'correctionBreakTimes'))
            ? $latestRequest->correctionBreakTimes
            : collect();


            // 表示ソース：申請の休憩 > 勤怠の休憩 > 空
            $breakSource = $requestBreaks->isNotEmpty() ? $requestBreaks : $attendanceBreaks;
            $breakCount = $breakSource->count() + 1;
        @endphp

        @for ($i = 0; $i < $breakCount; $i++)
            @php $break = $breakSource->get($i); @endphp
            <div class="detail-row">
                <div class="detail-label">{{ $i == 0 ? '休憩' : '休憩'.($i+1) }}</div>
                <div class="detail-value">
                    <div class="time-row">

                        <input type="text" class="time-input no-icon"
                            name="breaks[{{ $i }}][break_start]"
                            value="{{ $break && $break->break_start ? \Carbon\Carbon::createFromFormat('H:i:s', $break->break_start)->format('H:i') : '' }}"
                            pattern="[0-2][0-9]:[0-5][0-9]"
                            maxlength="5"
                            {{ $isPending ? 'disabled' : '' }}>

                        <span class="time-separator">～</span>

                        <input type="text" class="time-input no-icon"
                            name="breaks[{{ $i }}][break_end]"
                            value="{{ $break && $break->break_end ? \Carbon\Carbon::createFromFormat('H:i:s', $break->break_end)->format('H:i') : '' }}"
                            pattern="[0-2][0-9]:[0-5][0-9]"
                            maxlength="5"
                            {{ $isPending ? 'disabled' : '' }}>

                    </div>
                </div>
            </div>
        @endfor

        {{-- 備考 --}}
        <div class="detail-row">
            <div class="detail-label">備考</div>
            <div class="detail-value">
                <textarea class="note-input"
                    name="remarks"
                    {{ $isPending ? 'disabled' : '' }}>{{ $source->remarks ?? '' }}</textarea>
            </div>
        </div>
    </div>


    {{-- ボタン or 承認待ちメッセージ --}}
    <div class="button-container">
        @if ($isPending)
            <div class="pending-message">
                ＊承認待ちのため修正はできません。
            </div>
        @else
            <button type="submit" class="submit-button">修正</button>
        @endif
    </div>

    @if (!$isPending)
        </form>
    @endif

</div>

@endsection
