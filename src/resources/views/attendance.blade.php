@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="status">
    <span class="work-status">{{ $status }}</span>
</div>

@php
$days = ['日', '月', '火', '水', '木', '金', '土'];
$day = $days[now()->dayOfWeek];
@endphp
<div class="datetime">
    {{ now()->format('Y年m月d日') }}({{ $day }})
</div>

<script>
    function updateTime() {
        const now = new Date();

        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        document.getElementById('current-time').textContent =
            `${hours}:${minutes}`;
    }

    setInterval(updateTime, 1000);
    updateTime();
</script>
<div class="clock" id="current-time">
    --:--
</div>

<div class="attendance-form">
    @if ($status === '勤務外')
    <form action="{{ route('attendance.start') }}" method="POST">
        @csrf
        <button type="submit" class="attendance-button">出勤</button>
    </form>
    @elseif ($status === '出勤中')
    <div class="button-group" style="display: flex; gap: 20px; justify-content: center;">
        <form action="{{ route('attendance.end') }}" method="POST">
            @csrf
            <button type="submit" class="attendance-button finish-button">退勤</button>
        </form>
        <form action="{{ route('attendance.break-start') }}" method="POST">
            @csrf
            <button type="submit" class="attendance-button break-button">休憩入</button>
        </form>
    </div>
    @elseif ($status === '休憩中')
    <form action="{{ route('attendance.break-end') }}" method="POST">
        @csrf
        <button type="submit" class="attendance-button break-button">休憩戻</button>
    </form>
    @elseif ($status === '退勤済')
    <div class="finished-message">
        お疲れ様でした。
    </div>
    @endif
</div>
@endsection