@extends('layouts.app')

@section('title', '修正申請一覧')

@section('content')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">

<div class="container">
    <h1>修正申請一覧</h1>

    {{-- タブ切替 --}}
    <div class="tabs">
        <button class="tab active" data-target="pending">承認待ち</button>
        <button class="tab" data-target="approved">承認済み</button>
    </div>

    {{-- 承認待ち申請 --}}
    <div class="content" id="pending">
        <table>
            <thead>
                <tr>
                    <th>申請日</th>
                    <th>対象日</th>
                    <th>修正内容</th>
                    <th>備考</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingRequests as $request)
                <tr>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td>{{ $request->attendance_date->format('Y/m/d') }}</td>
                    <td>{{ $request->correction_type }}</td>
                    <td>{{ $request->note }}</td>
                    <td>
                        <a href="{{ route('attendanceCorrectionRequest.show', $request->id) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 承認済み申請 --}}
    <div class="content" id="approved" style="display:none;">
        <table>
            <thead>
                <tr>
                    <th>申請日</th>
                    <th>対象日</th>
                    <th>修正内容</th>
                    <th>備考</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvedRequests as $request)
                <tr>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td>{{ $request->attendance_date->format('Y/m/d') }}</td>
                    <td>{{ $request->correction_type }}</td>
                    <td>{{ $request->note }}</td>
                    <td>
                        <a href="{{ route('attendanceCorrectionRequest.show', $request->id) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const target = this.getAttribute('data-target');
            document.querySelectorAll('.content').forEach(c => c.style.display = 'none');
            document.getElementById(target).style.display = 'block';
        });
    });
</script>
@endsection
