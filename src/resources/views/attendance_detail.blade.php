@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="title">勤怠詳細</h1>

    @php
    $isAdminApproval = Auth::user()->role === 'admin' && $correction && $correction->status == 0;
    $isReadOnly = $isAdminApproval || ($correction && $correction->status == 1);
    @endphp

    <form action="{{ $isAdminApproval ? route('attendance.approve', $correction->id) : route('attendance.correction.store', $attendance->id) }}" method="POST">
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
                        @if($isReadOnly)
                        <span class="time-display-text">{{ $correction ? date('H:i', strtotime($correction->updated_clock_in)) : date('H:i', strtotime($attendance->clock_in)) }}</span>
                        <span class="range-separator">〜</span>
                        <span class="time-display-text">{{ $correction ? ($correction->updated_clock_out ? date('H:i', strtotime($correction->updated_clock_out)) : '') : ($attendance->clock_out ? date('H:i', strtotime($attendance->clock_out)) : '') }}</span>
                        @else
                        <input type="text" name="clock_in" class="time-input-field" value="{{ $correction ? date('H:i', strtotime($correction->updated_clock_in)) : date('H:i', strtotime($attendance->clock_in)) }}">
                        <span class="range-separator">〜</span>
                        <input type="text" name="clock_out" class="time-input-field" value="{{ $correction ? ($correction->updated_clock_out ? date('H:i', strtotime($correction->updated_clock_out)) : '') : ($attendance->clock_out ? date('H:i', strtotime($attendance->clock_out)) : '') }}">
                        @endif
                        @if($errors->has('clock_in') || $errors->has('clock_out'))
                        <div class="error-message">
                            {{ $errors->first('clock_in') ?: $errors->first('clock_out') }}
                        </div>
                        @endif
                    </td>
                </tr>

                @foreach($rests as $index => $rest)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td class="time-inputs">
                        @if($isReadOnly)
                        <span class="time-display-text">{{ $rest->break_start ? date('H:i', strtotime($rest->break_start)) : '' }}</span>
                        <span class="range-separator">〜</span>
                        <span class="time-display-text">{{ $rest->break_end ? date('H:i', strtotime($rest->break_end)) : '' }}</span>
                        @else
                        <input type="text" name="rests[{{ $index }}][start]" class="time-input-field" value="{{ $rest->break_start ? date('H:i', strtotime($rest->break_start)) : '' }}">
                        <span class="range-separator">〜</span>
                        <input type="text" name="rests[{{ $index }}][end]" class="time-input-field" value="{{ $rest->break_end ? date('H:i', strtotime($rest->break_end)) : '' }}">
                        @endif
                        @if($errors->has("rests.$index.start") || $errors->has("rests.$index.end"))
                        <div class="error-message">
                            {{ $errors->first("rests.$index.start") ?: $errors->first("rests.$index.end") }}
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td>
                        @if($isReadOnly)
                        <div class="remarks-display-text">{{ $correction ? $correction->remarks : '' }}</div>
                        @else
                        <textarea name="remarks" rows="5" class="remarks-area">{{ $correction ? $correction->remarks : '' }}</textarea>
                        @endif
                        @error('remarks')
                        <div class="error-message">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="form-actions">
            @if($isAdminApproval)
            <button type="submit" name="action" value="approve" class="attendance-button">承認</button>
            @elseif($correction && $correction->status == 0)
            <p class="lock-message">※承認待ちのため修正はできません。</p>
            @elseif($correction && $correction->status == 1)
            {{-- ここで「承認済み」を表示 --}}
            <div class="attendance-button approved-label" style="background-color: #000; color: #fff; cursor: default;">承認済み</div>
            @else
            <button type="submit" class="attendance-button">修正</button>
            @endif
        </div>

    </form>
</div>
@endsection