@extends('layouts.admin')

@section('title', '勤怠詳細（管理者編集）')

@section('content')

<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">

@if (session('success'))
    <div class="alert alert-success" style="padding: 10px; background: #d4edda; color: #155724; border-radius: 6px; margin-bottom: 15px;">
        {{ session('success') }}
    </div>
@endif

<div class="container">
    <h1>勤怠詳細</h1>

    <form
        action="{{ route('admin.attendance.update', $user->id) }}"
        method="POST"
        novalidate
    >
        @csrf
        @method('PUT')

        {{-- 🔽 新規作成対応（絶対に必要） --}}
        <input type="hidden" name="work_date" value="{{ $date }}">

        <div class="detail-card">

            {{-- 名前 --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-value">{{ $user->name }}</div>
            </div>

            {{-- 日付 --}}
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-value value-day">
                    <span class="year">{{ \Carbon\Carbon::parse($date)->format('Y年') }}</span>
                    <span class="month-day">{{ \Carbon\Carbon::parse($date)->format('n月j日') }}</span>
                </div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-value">
                    <div class="time-row">
                        <div class="input-wrapper">
                            <input
                                type="text"
                                name="clock_in"
                                class="time-input no-icon"
                                maxlength="5"
                                value="{{ old('clock_in', $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}"
                            >
                            @error('clock_in')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <span class="time-separator">～</span>

                        <div class="input-wrapper">
                            <input
                                type="text"
                                name="clock_out"
                                class="time-input no-icon"
                                maxlength="5"
                                value="{{ old('clock_out', $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}"
                            >
                            @error('clock_out')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 休憩 --}}
            @php
                $breaks = $attendance?->breakTimes ?? collect();
                $breakCount = max($breaks->count() + 1, 1);
            @endphp

            @for ($i = 0; $i < $breakCount; $i++)
                @php $break = $breaks->get($i); @endphp
                <div class="detail-row">
                    <div class="detail-label">{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</div>
                    <div class="detail-value">
                        <div class="time-row">
                            <div class="input-wrapper">
                                <input
                                    type="text"
                                    name="breaks[{{ $i }}][break_start]"
                                    class="time-input no-icon"
                                    maxlength="5"
                                    value="{{ old("breaks.$i.break_start", $break?->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '') }}"
                                >
                            </div>

                            <span class="time-separator">～</span>

                            <div class="input-wrapper">
                                <input
                                    type="text"
                                    name="breaks[{{ $i }}][break_end]"
                                    class="time-input no-icon"
                                    maxlength="5"
                                    value="{{ old("breaks.$i.break_end", $break?->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '') }}"
                                >
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
                        <textarea name="remarks" class="note-input">{{ old('remarks', $attendance?->remarks ?? '') }}</textarea>
                        @error('remarks')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

        </div>

        <div class="button-container">
            <button type="submit" class="submit-button">修正</button>
        </div>

    </form>
</div>

@endsection
