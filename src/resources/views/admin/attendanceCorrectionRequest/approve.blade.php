@extends('layouts.admin')

@section('title', '勤怠修正申請承認')

@section('content')

<link rel="stylesheet" href="{{ asset('css/request-detail.css') }}">

<div class="container">
    <h1>勤怠詳細</h1>

    <div class="detail-card">

        {{-- 申請者 --}}
        <div class="detail-row">
            <div class="detail-label">申請者</div>
            <div class="detail-value">{{ $requestData->user->name }}</div>
        </div>

        {{-- 日付 --}}
        <div class="detail-row">
            <div class="detail-label">対象日</div>
            <div class="detail-value value-day">
                <span class="year">{{ \Carbon\Carbon::parse($requestData->attendance->work_date)->format('Y年') }}</span>
                <span class="month-day">{{ \Carbon\Carbon::parse($requestData->attendance->work_date)->format('n月j日') }}</span>
            </div>
        </div>

        {{-- 出勤・退勤 --}}
        <div class="detail-row">
            <div class="detail-label">出勤・退勤</div>
            <div class="detail-value time-row">
                <div class="time-value">{{ $requestData->clock_in ? \Carbon\Carbon::parse($requestData->clock_in)->format('H:i') : '-' }}</div>
                <div class="time-separator">～</div>
                <div class="time-value">{{ $requestData->clock_out ? \Carbon\Carbon::parse($requestData->clock_out)->format('H:i') : '-' }}</div>
            </div>
        </div>

        {{-- 休憩時間 --}}
        @php
            $breaks = $requestData->correctionBreakTimes ?? collect();
            $breakCount = $breaks->count() + 1; // 取得したレコード + 空行1
        @endphp

        @for ($i = 0; $i < $breakCount; $i++)
            @php $break = $breaks->get($i); @endphp
            <div class="detail-row">
                <div class="detail-label">{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</div>
                <div class="detail-value time-row">
                    <div class="time-value">{{ $break && $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}</div>
                    <div class="time-separator">～</div>
                    <div class="time-value">{{ $break && $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}</div>
                </div>
            </div>
        @endfor

        {{-- 備考 --}}
        <div class="detail-row">
            <div class="detail-label">備考</div>
            <div class="detail-value">
                {{ $requestData->remarks ?? '-' }}
            </div>
        </div>

    </div>

    {{-- 承認ボタン --}}
    @php
        $isApproved = $requestData->status === 'approved';
    @endphp

    <form action="{{ route('admin.correction_request.approve', $requestData->id) }}" method="POST">
        @csrf
        <div class="button-container">
            <button type="submit" class="submit-button {{ $isApproved ? 'approved-button' : '' }}" {{ $isApproved ? 'disabled' : '' }}>
                {{ $isApproved ? '承認済み' : '承認' }}
            </button>
        </div>
    </form>

</div>

@endsection
