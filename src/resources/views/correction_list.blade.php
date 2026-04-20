@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
<link rel="stylesheet" href="{{ asset('css/correction_list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="title">申請一覧</h1>

    <div class="tab-navigation">
        <a href="{{ route('correction.list', ['tab' => 'pending']) }}" class="tab-item {{ $tab === 'pending' ? 'active' : '' }}">承認待ち</a>
        <a href="{{ route('correction.list', ['tab' => 'approved']) }}" class="tab-item {{ $tab === 'approved' ? 'active' : '' }}">承認済み</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($corrections as $correction)
            <tr>
                <td>
                    <span class="status-label {{ $correction->status == 0 ? 'is-pending' : ($correction->status == 1 ? 'is-approved' : 'is-rejected') }}">
                        {{ $correction->status == 0 ? '承認待ち' : ($correction->status == 1 ? '承認済み' : '却下') }}
                    </span>
                </td>
                <td>{{ $correction->user->name }}</td>
                <td>{{ \Carbon\Carbon::parse($correction->attendance->work_date)->format('Y/m/d') }}</td>
                <td>{{ $correction->remarks }}</td>
                <td>{{ $correction->created_at->format('Y/m/d') }}</td>
                <td>
                    <a href="{{ route('attendance.show', $correction->attendance_id) }}" class="btn-primary">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection