@extends('layouts.admin')

@section('title', '勤怠修正申請一覧')

@section('content')
<link rel="stylesheet" href="{{ asset('css/attendance_correction_list.css') }}">

<div class="correction-container">
    <h1>勤怠修正申請一覧</h1>

    <div class="tabs">
        <a href="{{ route('admin.correction_request.list', ['status' => 'pending']) }}"
           class="tab {{ ($status ?? 'pending') === 'pending' ? 'active' : '' }}">
            承認待ち
        </a>

        <a href="{{ route('admin.correction_request.list', ['status' => 'approved']) }}"
           class="tab {{ ($status ?? 'pending') === 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    <table class="correction-table">
        <thead>
            <tr>
                <th>状態</th>
                <th>申請者</th>
                <th>対象日</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($requests as $req)
                <tr>
                    <td>
                        {{ $req->status === 'pending' ? '承認待ち' : '承認済み' }}
                    </td>

                    <td>
                        {{ $req->user->name ?? '不明' }}
                    </td>

                    <td>
                        {{ $req->work_date
                            ? \Carbon\Carbon::parse($req->work_date)->format('Y/m/d')
                            : ($req->attendance
                                ? \Carbon\Carbon::parse($req->attendance->work_date)->format('Y/m/d')
                                : '(未登録)') }}
                    </td>

                    <td>{{ $req->remarks }}</td>

                    <td>{{ $req->created_at->format('Y/m/d') }}</td>

                    <td>
                        <a href="{{ route('admin.correction_request.show', ['id' => $req->id]) }}">
                            詳細
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">
                        申請はありません。
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-wrapper">
        @if ($requests->lastPage() > 1)
            <ul class="pagination">
                <li class="{{ $requests->onFirstPage() ? 'disabled' : '' }}">
                    <a href="{{ $requests->url(1) }}">&laquo;</a>
                </li>

                <li class="{{ $requests->onFirstPage() ? 'disabled' : '' }}">
                    <a href="{{ $requests->previousPageUrl() }}">&lt;</a>
                </li>

                @for ($i = 1; $i <= $requests->lastPage(); $i++)
                    <li class="{{ $i == $requests->currentPage() ? 'active' : '' }}">
                        <a href="{{ $requests->url($i) }}">{{ $i }}</a>
                    </li>
                @endfor

                <li class="{{ $requests->hasMorePages() ? '' : 'disabled' }}">
                    <a href="{{ $requests->nextPageUrl() }}">&gt;</a>
                </li>

                <li class="{{ $requests->hasMorePages() ? '' : 'disabled' }}">
                    <a href="{{ $requests->url($requests->lastPage()) }}">&raquo;</a>
                </li>
            </ul>
        @endif
    </div>
</div>
@endsection
