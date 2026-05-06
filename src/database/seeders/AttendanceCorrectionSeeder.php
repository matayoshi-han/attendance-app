<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\RestCorrection;
use Carbon\Carbon;

class AttendanceCorrectionSeeder extends Seeder
{
    public function run()
    {
        // 1. 申請を出させたい勤怠データをいくつかピックアップ（例：最初の3件）
        $attendances = Attendance::take(3)->get();

        foreach ($attendances as $attendance) {
            // 2. 修正申請データの作成
            $correction = AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                // 元の出勤の30分後、退勤の30分前などでリアルな修正案を作る
                'updated_clock_in' => Carbon::parse($attendance->clock_in)->addMinutes(30),
                'updated_clock_out' => Carbon::parse($attendance->clock_out)->subMinutes(30),
                'remarks' => '遅延のため',
                'status' => 0, // 0: 承認待ち
            ]);

            // 3. 休憩の修正データも作成
            RestCorrection::create([
                'attendance_correction_id' => $correction->id,
                'updated_break_start' => $attendance->work_date . ' 12:00:00',
                'updated_break_end' => $attendance->work_date . ' 13:00:00',
            ]);
        }
    }
}
