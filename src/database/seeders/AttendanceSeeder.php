<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        // 1. 指定されたスタッフ情報（名前とメールアドレス）
        $staffs = [
            ['name' => '西 伶奈', 'email' => 'reina.n@coachtech.com'],
            ['name' => '山田 太郎', 'email' => 'taro.y@coachtech.com'],
            ['name' => '増田 一世', 'email' => 'issei.m@coachtech.com'],
            ['name' => '山本 敬吉', 'email' => 'keikichi.y@coachtech.com'],
            ['name' => '秋田 朋美', 'email' => 'tomomi.a@coachtech.com'],
            ['name' => '中西 教夫', 'email' => 'norio.n@coachtech.com'],
        ];

        foreach ($staffs as $staff) {
            // ユーザーの作成
            $user = User::create([
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => Hash::make('password'),
                'role' => 'user',
            ]);

            // 2. 開始日を 2026年3月1日 に設定
            $startDate = Carbon::create(2026, 3, 1, 0, 0, 0);

            // 3. 31日分（3月末まで）ループ
            for ($i = 0; $i < 31; $i++) {
                $date = $startDate->copy()->addDays($i);

                // 土日は勤務なし
                if ($date->isWeekend()) continue;

                // 勤怠メインデータの作成
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date->toDateString(),
                    'clock_in' => $date->copy()->hour(9)->minute(rand(0, 15))->second(0),
                    'clock_out' => $date->copy()->hour(18)->minute(rand(0, 30))->second(0),
                ]);

                // 休憩データの作成（お昼休憩）
                Rest::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $date->copy()->hour(12)->minute(0)->second(0),
                    'break_end' => $date->copy()->hour(13)->minute(0)->second(0),
                ]);

                // 確率で午後の休憩を追加
                if (rand(1, 3) === 1) {
                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $date->copy()->hour(15)->minute(0)->second(0),
                        'break_end' => $date->copy()->hour(15)->minute(15)->second(0),
                    ]);
                }
            }
        }
    }
}
