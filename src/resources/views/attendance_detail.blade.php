@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="title">勤怠詳細</h1>

    <form action="{{ route('attendance.correction.store', $attendance->id) }}" method="POST">
        @csrf
        <table class="table detail-table">
            <tbody>
                <tr>
                    <th>名前</th>
                    <td>
                        <div class="name-display">
                            <span class="name-text">{{ $attendance->user->name }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        <div class="date-display">
                            <span class="date-year">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                            <span class="date-monthday">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td class="time-inputs">
                        <input type="text" name="clock_in" class="time-input-field"
                            value="{{ $correction ? date('H:i', strtotime($correction->updated_clock_in)) : date('H:i', strtotime($attendance->clock_in)) }}">

                        <span class="range-separator">〜</span>

                        <input type="text" name="clock_out" class="time-input-field"
                            value="{{ $correction ? ($correction->updated_clock_out ? date('H:i', strtotime($correction->updated_clock_out)) : '') : ($attendance->clock_out ? date('H:i', strtotime($attendance->clock_out)) : '') }}">
                    </td>
                </tr>

                @foreach($rests as $index => $rest)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td class="time-inputs">
                        <input type="text" name="rests[{{ $index }}][start]" class="time-input-field"
                            value="{{ $rest->break_start ? date('H:i', strtotime($rest->break_start)) : '' }}">
                        <span class="range-separator">〜</span>
                        <input type="text" name="rests[{{ $index }}][end]" class="time-input-field"
                            value="{{ $rest->break_end ? date('H:i', strtotime($rest->break_end)) : '' }}">
                    </td>
                </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td>
                        <textarea name="remarks" rows="5" class="remarks-area">{{ $correction ? $correction->remarks : '' }}</textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="form-actions">
            @if($correction && $correction->status == 0)
            <p class="lock-message">※承認待ちのため修正はできません。</p>
            @else
            <button type="submit" class="attendance-button">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection