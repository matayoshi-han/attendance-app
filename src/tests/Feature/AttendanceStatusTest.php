<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 勤務外の場合、勤怠ステータスが「勤務外」と表示される
     */
    public function test_status_is_off_work()
    {
        $user = User::factory()->create();

        // 勤怠データが一切ない状態で画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * 2. 出勤中の場合、勤怠ステータスが「出勤中」と表示される
     */
    public function test_status_is_working()
    {
        $user = User::factory()->create();

        // 出勤打刻済みのデータを作成
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 3. 休憩中の場合、勤怠ステータスが「休憩中」と表示される
     */
    public function test_status_is_on_break()
    {
        $user = User::factory()->create();

        // 出勤中、かつ終了していない休憩データがある状態を作る
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now(),
        ]);

        Rest::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * 4. 退勤済の場合、勤怠ステータスが「退勤済」と表示される
     */
    public function test_status_is_finished()
    {
        $user = User::factory()->create();

        // 退勤打刻済みのデータを作成
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(9),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
