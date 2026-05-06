<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 退勤ボタンが正しく機能する
     */
    public function test_clock_out_button_works_correctly()
    {
        $user = User::factory()->create();
        $today = now()->toDateString();

        // 1. 出勤状態にする
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            'clock_in' => $today . ' 09:00:00',
        ]);

        // 2. 画面に「退勤」ボタンが表示されていることを確認
        $response = $this->actingAs($user)->get('/attendance');
        // ヘッダーの文字と区別するため、ボタンタグの形式で確認
        $response->assertSee('退勤</button>', false);

        // 3. 退勤処理を行う
        $this->post(route('attendance.end'));

        // 4. ステータスが「退勤済」になることを確認
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    /**
     * 2. 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_out_time_is_visible_on_attendance_list()
    {
        $user = User::factory()->create();
        $today = now()->toDateString();
        $clockOutTime = '18:00:00';

        // 1. 出勤処理を行う
        $this->actingAs($user)->post(route('attendance.start'));

        // 2. 退勤処理を行う
        // テスト環境で特定の時刻を記録するため、一度退勤してからDBを更新、またはCarbonで固定します
        $this->post(route('attendance.end'));

        // 記録された退勤時刻をテスト用に「18:00」に書き換え
        Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->update(['clock_out' => $today . ' ' . $clockOutTime]);

        // 3. 勤怠一覧画面から退勤の日付（時刻）を確認する
        $response = $this->get('/attendance/list');

        // 退勤時刻が正確に表示されているか（秒なしの形式）
        $response->assertSee('18:00');
    }
}
