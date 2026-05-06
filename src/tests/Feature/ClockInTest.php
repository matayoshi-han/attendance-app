<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 出勤ボタンが正しく機能する
     */
    public function test_clock_in_button_works_correctly()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤');

        $this->post(route('attendance.start'));

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /**
     * 2. 出勤は一日一回のみできる
     */
    public function test_clock_in_button_is_not_visible_after_clock_out()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            'clock_in' => $today . ' 09:00:00',
            'clock_out' => $today . ' 18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        // 【修正】ヘッダーの「出勤一覧」という文字は無視し、
        // ボタンとしての「出勤</button>」が存在しないことを確認
        $response->assertDontSee('出勤</button>', false);

        // 完了メッセージが表示されていることを確認
        $response->assertSee('お疲れ様でした');
    }

    /**
     * 3. 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_in_time_is_visible_on_attendance_list()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();
        $clockInDateTime = $today . ' 09:15:00';

        // 出勤処理を行う
        $this->actingAs($user)->post(route('attendance.start'));

        // Datetime形式でレコードを更新
        Attendance::where('user_id', $user->id)->update(['clock_in' => $clockInDateTime]);

        $response = $this->get('/attendance/list');

        // 画面上では「時:分」形式で表示されているか確認
        $response->assertSee('09:15');
    }
}
