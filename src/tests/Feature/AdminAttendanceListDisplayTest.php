<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdminAttendanceListDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 管理者ユーザーを作成
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * 1. その日の全ユーザーの勤怠情報が正確に表示されることを確認
     */
    public function test_admin_can_see_all_users_attendance_correctly()
    {
        $today = Carbon::today();

        // 複数ユーザーの勤怠データを作成
        $user1 = User::factory()->create(['name' => 'スタッフA']);
        Attendance::create([
            'user_id' => $user1->id,
            'work_date' => $today->toDateString(),
            'clock_in' => $today->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'),
            'clock_out' => $today->copy()->setTime(18, 0, 0)->format('Y-m-d H:i:s'),
        ]);

        $user2 = User::factory()->create(['name' => 'スタッフB']);
        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => $today->toDateString(),
            'clock_in' => $today->copy()->setTime(10, 0, 0)->format('Y-m-d H:i:s'),
            'clock_out' => $today->copy()->setTime(19, 0, 0)->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('スタッフA');
        $response->assertSee('09:00');
        $response->assertSee('スタッフB');
        $response->assertSee('10:00');
    }

    /**
     * 2. 遷移した際に現在の日付が表示される
     */
    public function test_admin_attendance_list_shows_current_date()
    {
        $now = Carbon::now();

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list'));

        // 画面上に今日の日付（例: 2024/10/15）が表示されているか
        $response->assertSee($now->format('Y/m/d'));
    }

    /**
     * 3. 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_admin_attendance_list_can_navigate_to_previous_day()
    {
        $prevDay = Carbon::yesterday();
        $user = User::factory()->create(['name' => '昨日働いた人']);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $prevDay->toDateString(),
            'clock_in' => $prevDay->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'),
        ]);

        // 前日の日付パラメータを渡してアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list', ['date' => $prevDay->toDateString()]));

        $response->assertStatus(200);
        $response->assertSee($prevDay->format('Y/m/d'));
        $response->assertSee('昨日働いた人');
    }

    /**
     * 4. 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_admin_attendance_list_can_navigate_to_next_day()
    {
        $nextDay = Carbon::tomorrow();

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list', ['date' => $nextDay->toDateString()]));

        $response->assertStatus(200);
        $response->assertSee($nextDay->format('Y/m/d'));
    }
}
