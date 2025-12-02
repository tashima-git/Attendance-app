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

    // 表示ソース（申請 > 勤怠 > 空）
    $source = $latestRequest ?? $attendance;
@endphp

<div class="container">
    <h1>勤怠詳細</h1>

    {{-- 申請中でない場合はフォームを表示 --}}
    @if (!$isPending)
        <form action="{{ route('correction_request.store') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id ?? '' }}">
            <input type="hidden" name="work_date" value="{{ $attendance->work_date }}">
    @endif

    <div class="detail-card">

        {{-- 名前 --}}
        <div class="detail-row">
            <div class="detail-label">名前</div>
            <div class="detail-value">{{ $attendance->user->name }}</div>
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
                    <div class="input-wrapper">
                        <input type="text" name="clock_in" class="time-input no-icon"
                               value="{{ old('clock_in', $source->clock_in ? \Carbon\Carbon::parse($source->clock_in)->format('H:i') : '') }}"
                               maxlength="5" {{ $isPending ? 'disabled' : '' }}>
                        @error('clock_in')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <span class="time-separator">～</span>

                    <div class="input-wrapper">
                        <input type="text" name="clock_out" class="time-input no-icon"
                               value="{{ old('clock_out', $source->clock_out ? \Carbon\Carbon::parse($source->clock_out)->format('H:i') : '') }}"
                               maxlength="5" {{ $isPending ? 'disabled' : '' }}>
                        @error('clock_out')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 休憩時間 --}}
        @php
            $attendanceBreaks = $attendance->breakTimes ?? collect();
            $requestBreaks = ($latestRequest && method_exists($latestRequest, 'correctionBreakTimes'))
                ? $latestRequest->correctionBreakTimes
                : collect();
            $breakSource = $requestBreaks->isNotEmpty() ? $requestBreaks : $attendanceBreaks;
            $breakCount = $breakSource->count() + 1;
        @endphp

        @for ($i = 0; $i < $breakCount; $i++)
            @php $break = $breakSource->get($i); @endphp
            <div class="detail-row">
                <div class="detail-label">{{ $i == 0 ? '休憩' : '休憩'.($i+1) }}</div>
                <div class="detail-value">
                    <div class="time-row">
                        <div class="input-wrapper">
                            <input type="text" name="breaks[{{ $i }}][break_start]"
                                   class="time-input no-icon"
                                   value="{{ old("breaks.$i.break_start", $break && $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '') }}"
                                   maxlength="5" {{ $isPending ? 'disabled' : '' }}>
                            @error("breaks.$i.break_start")
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <span class="time-separator">～</span>

                        <div class="input-wrapper">
                            <input type="text" name="breaks[{{ $i }}][break_end]"
                                   class="time-input no-icon"
                                   value="{{ old("breaks.$i.break_end", $break && $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '') }}"
                                   maxlength="5" {{ $isPending ? 'disabled' : '' }}>
                            @error("breaks.$i.break_end")
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        @endfor

        {{-- 備考 --}}
        <div class="detail-row">
            <div class="detail-label">備考</div>
            <div class="detail-value">
                <div class="input-wrapper">
                    <textarea name="remarks" class="note-input" {{ $isPending ? 'disabled' : '' }}>{{ old('remarks', $source->remarks ?? '') }}</textarea>
                    @error('remarks')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

    </div>

    {{-- ボタン or 承認待ち --}}
    <div class="button-container">
        @if ($isPending)
            <div class="pending-message">＊承認待ちのため修正はできません。</div>
        @else
            <button type="submit" class="submit-button">修正</button>
        @endif
    </div>

    @if (!$isPending)
        </form>
    @endif
</div>

@endsection
