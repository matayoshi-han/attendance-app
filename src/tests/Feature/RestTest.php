<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 休憩ボタンが正しく機能する
     */
    public function test_break_start_button_works_correctly()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            'clock_in' => $today . ' 09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');

        // 休憩入を実行
        $this->post(route('attendance.break-start'));

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    /**
     * 2. 休憩は一日に何回でもできる（2回目の休憩入ボタン確認）
     */
    public function test_can_take_multiple_breaks_start_check()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            'clock_in' => $today . ' 09:00:00',
        ]);

        // 1回目の休憩（開始→終了）
        Rest::create([
            'attendance_id' => $attendance->id,
            'break_start' => $today . ' 12:00:00',
            'break_end' => $today . ' 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        // 再び「休憩入」が表示される
        $response->assertSee('休憩入');
    }

    /**
     * 3. 休憩戻ボタンが正しく機能する
     */
    public function test_break_end_button_works_correctly()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            'clock_in' => $today . ' 09:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendance->id,
            'break_start' => $today . ' 12:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩戻');

        // 休憩戻を実行
        $this->post(route('attendance.break-end'));

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /**
     * 4. 休憩戻は一日に何回でもできる（2回目の休憩戻ボタン確認）
     */
    /**
     * 4. 休憩戻は一日に何回でもできる（2回目の休憩戻ボタン確認）
     */
    public function test_can_take_multiple_breaks_end_check()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. 出勤 (10:00)
        Carbon::setTestNow(now()->parse('10:00:00'));
        $this->post(route('attendance.start'));

        // 2. 1回目の休憩開始 (12:00)
        Carbon::setTestNow(now()->parse('12:00:00'));
        $this->post(route('attendance.break-start'));

        // 3. 1回目の休憩終了 (13:00)
        Carbon::setTestNow(now()->parse('13:00:00'));
        $this->post(route('attendance.break-end'));

        // 4. 2回目の休憩開始 (15:00)
        // ここで時間を進めることで、created_atが他のレコードより新しくなり、
        // コントローラーの latest() が必ずこのレコードを拾うようになります
        Carbon::setTestNow(now()->parse('15:00:00'));
        $this->post(route('attendance.break-start'));

        // 5. 画面を表示
        $response = $this->get('/attendance');

        // テスト時刻の固定を解除
        Carbon::setTestNow();

        // 検証
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');
    }



    /**
     * 5. 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_break_time_is_visible_on_attendance_list()
    {
        $user = User::factory()->create();
        $today = Carbon::today()->toDateString();

        // 1. 出勤・退勤データを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            'clock_in' => $today . ' 09:00:00',
            'clock_out' => $today . ' 18:00:00',
        ]);

        // 2. 休憩データを1時間分作成（こちらを合計して表示していると推測）
        $attendance->rests()->create([
            'break_start' => $today . ' 12:00:00',
            'break_end' => $today . ' 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        // 3. 表示の検証
        // 合計1時間の休憩が「01:00」として表示されていることを確認
        $response->assertSee('01:00');
    }
}