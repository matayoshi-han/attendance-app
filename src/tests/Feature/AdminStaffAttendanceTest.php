<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdminStaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 管理者ユーザーの作成
        $this->admin = User::factory()->create(['role' => 'admin']);
        // テスト対象となる一般スタッフの作成
        $this->staff = User::factory()->create([
            'name' => 'スタッフA',
            'email' => 'staff_a@example.com',
            'role' => 'user'
        ]);
    }

    /**
     * 1. 管理者が全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_admin_can_view_all_staff_info()
    {
        // 従業員一覧ルート (admin.user.list) をテスト
        $response = $this->actingAs($this->admin)->get(route('admin.user.list'));

        $response->assertStatus(200);
        $response->assertSee('スタッフA');
        $response->assertSee('staff_a@example.com');
    }

    /**
     * 2. 選択したユーザーの勤怠情報が正しく表示される
     */
    public function test_admin_can_view_selected_staff_attendance()
    {
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $this->staff->id,
            'work_date' => $today->toDateString(),
            'clock_in' => $today->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'),
        ]);

        // 管理者用の個別スタッフ勤怠一覧ルートをテスト
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.staff', ['user_id' => $this->staff->id]));

        $response->assertStatus(200);
        // 実装したタイトル「スタッフAさんの勤怠一覧」が含まれているか
        $response->assertSee('スタッフAさんの勤怠一覧');
        $response->assertSee($today->format('m/d'));
        $response->assertSee('09:00');
    }

    /**
     * 3. 「前月」ボタンを押下した時に前月の情報が表示される
     */
    public function test_admin_can_navigate_to_staff_previous_month()
    {
        $prevMonth = Carbon::now()->subMonth();

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.staff', [
            'user_id' => $this->staff->id,
            'month' => $prevMonth->format('Y-m')
        ]));

        $response->assertStatus(200);
        // 画面上の月表示 (例: 2024/04) を確認
        $response->assertSee($prevMonth->format('Y/m'));
    }

    /**
     * 4. 「翌月」ボタンを押下した時に翌月の情報が表示される
     */
    public function test_admin_can_navigate_to_staff_next_month()
    {
        $nextMonth = Carbon::now()->addMonth();

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.staff', [
            'user_id' => $this->staff->id,
            'month' => $nextMonth->format('Y-m')
        ]));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /**
     * 5. 「詳細」ボタンを押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_admin_can_navigate_from_staff_list_to_detail()
    {
        $attendance = Attendance::create([
            'user_id' => $this->staff->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => now()->format('Y-m-d H:i:s'),
        ]);

        // スタッフ月別一覧を表示
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.staff', ['user_id' => $this->staff->id]));

        // 管理者用の詳細・修正画面へのURL (admin.attendance.edit) が含まれているか
        $detailUrl = route('admin.attendance.edit', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);

        // 実際に詳細画面へ遷移して表示を確認
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('スタッフA'); // 修正画面内にスタッフ名があるか
        $detailResponse->assertSee('勤怠詳細'); // 修正画面のタイトル
    }
}
