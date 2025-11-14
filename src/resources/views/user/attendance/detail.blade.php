@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1>勤怠詳細</h1>

    <form action="{{ route('correction_request.store') }}" method="POST">
        @csrf
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

        <div class="detail-card">
            {{-- 名前 --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-value">{{ $attendance->user->name }}</div>
            </div>

            {{-- 日付 --}}
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-value">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年n月j日') }}</div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-value">
                    <div class="time-row">
                        <input type="time" class="time-input" name="clock_in" value="{{ $attendance->clock_in }}">
                        <span class="time-separator">～</span>
                        <input type="time" class="time-input" name="clock_out" value="{{ $attendance->clock_out }}">
                    </div>
                </div>
            </div>

            {{-- 休憩1 --}}
            <div class="detail-row">
                <div class="detail-label">休憩</div>
                <div class="detail-value">
                    @php
                        $break1 = $attendance->breakTimes->get(0);
                    @endphp
                    <div class="time-row">
                        <input type="time" class="time-input" name="break_start" value="{{ $break1->break_start ?? '' }}">
                        <span class="time-separator">～</span>
                        <input type="time" class="time-input" name="break_end" value="{{ $break1->break_end ?? '' }}">
                    </div>
                </div>
            </div>

            {{-- 休憩2 --}}
            <div class="detail-row">
                <div class="detail-label">休憩2</div>
                <div class="detail-value">
                    @php
                        $break2 = $attendance->breakTimes->get(1);
                    @endphp
                    <div class="time-row">
                        <input type="time" class="time-input" name="break2_start" value="{{ $break2->break_start ?? '' }}">
                        <span class="time-separator">～</span>
                        <input type="time" class="time-input" name="break2_end" value="{{ $break2->break_end ?? '' }}">
                    </div>
                </div>
            </div>

            {{-- 備考 --}}
            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-value">
                    <textarea class="note-input" name="reason" placeholder="修正理由を入力">{{ optional($attendance->correctionRequests->last())->reason ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <div class="button-container">
            <button type="submit" class="submit-button">修正申請</button>
        </div>
    </form>
</div>
@endsection


