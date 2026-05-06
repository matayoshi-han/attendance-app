@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="title">
        @if(Auth::user()->role === 'admin' && isset($targetUser))
        {{ $targetUser->name }}の勤怠一覧
        @else
        勤怠一覧
        @endif
    </h1>

    <div class="month-navigation">
        <a href="{{ route('attendance.list', ['month' => $prevMonth, 'user_id' => $userId]) }}" class="nav-btn"><img src="{{ asset('images/arrow.png') }}" alt="前月" class="arrow-left"><span>前月</span></a>
        <span class="month-display"><img src="{{ asset('images/calendar-icon.png') }}" alt="カレンダー" class="calendar-icon">{{ $currentMonth->format('Y/m') }}</span>
        <a href="{{ route('attendance.list', ['month' => $nextMonth, 'user_id' => $userId]) }}" class="nav-btn"><span>翌月</span><img src="{{ asset('images/arrow.png') }}" alt="次月" class="arrow-right"></a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>勤務</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            @php
            $date = \Carbon\Carbon::parse($attendance->work_date);
            $days = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeek = $days[$date->dayOfWeek];
            @endphp
            <tr>
                <td>{{ $date->format('m/d') }}({{ $dayOfWeek }})</td>
                <td>{{ $attendance->clock_in ? date('H:i', strtotime($attendance->clock_in)) : '-' }}</td>
                <td>{{ $attendance->clock_out ? date('H:i', strtotime($attendance->clock_out)) : '-' }}</td>
                <td>{{ $attendance->total_rest_time ? substr($attendance->total_rest_time, 0, 5) : '-' }}</td>
                <td>{{ $attendance->total_work_time ? substr($attendance->total_work_time, 0, 5) : '-' }}</td>
                <td>
                    <a href="{{ route('attendance.show', $attendance->id) }}" class="btn-primary">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if(Auth::user()->role === 'admin')
    <div class="csv-export">
        <a href="{{ route('attendance.export', ['month' => $currentMonth->format('Y-m'), 'user_id' => $userId]) }}" class="csv-btn">
            CSV出力
        </a>
    </div>
    @endif
</div>
@endsection