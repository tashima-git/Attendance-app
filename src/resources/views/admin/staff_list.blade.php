@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@section('content')

<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">

<h1>スタッフ一覧</h1>

<table class="attendance-table">
    <thead>
        <tr>
            <th class="name-cell">名前</th>
            <th>メールアドレス</th>
            <th>月次勤怠</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($users as $user)
            <tr>
                <td class="name-cell">{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <a href="{{ route('admin.attendance.staff', ['id' => $user->id]) }}"
   class="status status-calculated">
    詳細
</a>


                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="no-data">
                    スタッフが登録されていません
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 20px;">
    {{ $users->links() }}
</div>

@endsection
