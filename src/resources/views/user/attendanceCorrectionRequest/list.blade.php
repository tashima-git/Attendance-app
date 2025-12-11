@extends('layouts.app')

@section('title', '申請一覧')

@section('content')

<link rel="stylesheet" href="{{ asset('css/attendance_correction_list.css') }}">

<div class="correction-container">

    <h1>申請一覧</h1>

    {{-- タブ --}}
    <div class="tabs">
        <a href="{{ route('correction_request.index', ['status' => 'pending']) }}"
           class="tab {{ $status === 'pending' ? 'active' : '' }}">
            承認待ち
        </a>

        <a href="{{ route('correction_request.index', ['status' => 'approved']) }}"
           class="tab {{ $status === 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    {{-- テーブル --}}
    <table class="correction-table">
        <thead>
            <tr>
                <th>状態</th>
                <th>申請者</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
        @forelse ($requests as $req)
            <tr>
                {{-- 状態 --}}
                <td>
                    {{ $req->status === 'pending' ? '承認待ち' : '承認済み' }}
                </td>

                {{-- 申請者名 --}}
                <td>
                    {{ $req->user->name ?? '不明' }}
                </td>

                {{-- 対象日 --}}
{{-- 対象日 --}}
<td>
    {{ $req->work_date 
        ? \Carbon\Carbon::parse($req->work_date)->format('Y/m/d') 
        : ($req->attendance ? \Carbon\Carbon::parse($req->attendance->work_date)->format('Y/m/d') : '(未登録)') }}
</td>


                {{-- 申請理由 --}}
                <td>{{ $req->remarks }}</td>

                {{-- 申請日時 --}}
                <td>{{ $req->created_at->format('Y/m/d') }}</td>

                {{-- 詳細リンク --}}
@php
    $targetDate = $req->work_date ?? ($req->attendance->work_date ?? null);
@endphp

<td>
    @if ($targetDate)
        <a href="{{ route('attendance.show', ['id' => $targetDate]) }}">
            詳細
        </a>
    @else
        ―
    @endif
</td>


            </tr>
        @empty
            <tr>
                <td colspan="6" class="empty">申請はありません。</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{-- ページネーション --}}
    <div class="pagination-wrapper">
        {{ $requests->links() }}
    </div>
</div>

@endsection
