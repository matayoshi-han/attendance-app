@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="title">{{ \Carbon\Carbon::parse($currentDate)->format('Y年n月j日') }}の勤怠一覧</h1>

    <div class="month-navigation">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="nav-btn">
            <img src="{{ asset('images/arrow.png') }}" alt="前日" class="arrow-left"><span>前日</span>
        </a>

        <span class="month-display">
            <img src="{{ asset('images/calendar-icon.png') }}" alt="カレンダー" class="calendar-icon">
            {{ \Carbon\Carbon::parse($currentDate)->format('Y/m/d') }}
        </span>

        <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="nav-btn">
            <span>翌日</span><img src="{{ asset('images/arrow.png') }}" alt="翌日" class="arrow-right">
        </a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>勤務</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance->user->name }}</td>
                <td>{{ $attendance->clock_in ? date('H:i', strtotime($attendance->clock_in)) : '-' }}</td>
                <td>{{ $attendance->clock_out ? date('H:i', strtotime($attendance->clock_out)) : '-' }}</td>
                <td>{{ $attendance->total_rest_time ? substr($attendance->total_rest_time, 0, 5) : '-' }}</td>
                <td>{{ $attendance->total_work_time ? substr($attendance->total_work_time, 0, 5) : '-' }}</td>
                <td>
                    <a href="{{ route('admin.attendance.edit', ['id' => $attendance->id]) }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection