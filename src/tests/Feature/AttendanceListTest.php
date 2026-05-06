<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 自分の勤怠情報がすべて表示されていることを確認
     */
    public function test_user_can_see_own_attendance_data()
    {
        $user = User::factory()->create();
        $today = Carbon::today();

        // 勤怠データを登録 (at() を setTime() に修正)
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'clock_in' => $today->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'),
            'clock_out' => $today->copy()->setTime(18, 0, 0)->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($today->format('m/d'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 2. 勤怠一覧ページを開くと現在の月が表示されている
     */
    public function test_attendance_list_shows_current_month()
    {
        $user = User::factory()->create();
        $now = Carbon::now();

        $response = $this->actingAs($user)->get('/attendance/list');

        // ヘッダーやナビゲーションに現在の年月（例: 2024/10）が表示されているか
        $response->assertSee($now->format('Y/m'));
    }

    /**
     * 3. 「前月」ボタンを押すと前月の情報が表示される
     */
    public function test_attendance_list_can_navigate_to_previous_month()
    {
        $user = User::factory()->create();
        $prevMonth = Carbon::now()->subMonth();

        // 前月のリンクを押した状態をシミュレート
        $response = $this->actingAs($user)->get(route('attendance.list', ['month' => $prevMonth->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertSee($prevMonth->format('Y/m'));
    }

    /**
     * 4. 「翌月」ボタンを押すと翌月の情報が表示される
     */
    public function test_attendance_list_can_navigate_to_next_month()
    {
        $user = User::factory()->create();
        $nextMonth = Carbon::now()->addMonth();

        // 翌月のリンクを押した状態をシミュレート
        $response = $this->actingAs($user)->get(route('attendance.list', ['month' => $nextMonth->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /**
     * 5. 「詳細」ボタンを押下するとその日の勤怠詳細画面に遷移する
     */
    public function test_attendance_list_detail_button_navigates_to_detail_page()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        // 詳細ボタンのリンク先URLが含まれているか確認
        $detailUrl = route('attendance.show', $attendance->id);
        $response->assertSee($detailUrl);

        // 実際にアクセスして200が返るか確認
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
    }
}
