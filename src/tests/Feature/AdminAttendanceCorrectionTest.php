<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdminAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 管理者ユーザーを作成
        $this->admin = User::factory()->create(['role' => 'admin']);

        // テスト用のスタッフと勤怠データを作成
        $this->staff = User::factory()->create(['name' => 'テストスタッフ']);
        $this->attendance = Attendance::create([
            'user_id' => $this->staff->id,
            'work_date' => '2024-10-15',
            'clock_in' => '2024-10-15 09:00:00',
            'clock_out' => '2024-10-15 18:00:00',
        ]);
    }

    /**
     * 1. 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_admin_can_view_selected_attendance_detail()
    {
        $response = $this->actingAs($this->admin)->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        $response->assertSee('テストスタッフ');
        $response->assertSee('2024年');
        $response->assertSee('10月15日');
    }

    /**
     * 2. 出勤時間が退勤時間より後になっている場合のエラー
     */
    public function test_admin_clock_in_after_clock_out_error()
    {
        $response = $this->actingAs($this->admin)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'remarks' => '管理者による修正',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 3. 休憩開始時間が退勤時間より後になっている場合のエラー
     */
    public function test_admin_break_start_after_clock_out_error()
    {
        $response = $this->actingAs($this->admin)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['start' => '19:00', 'end' => '20:00']],
            'remarks' => '管理者による修正',
        ]);

        $response->assertSessionHasErrors([
            'rests.0.start' => '休憩時間が不適切な値です'
        ]);
    }

    /**
     * 4. 休憩終了時間が退勤時間より後になっている場合のエラー
     */
    public function test_admin_break_end_after_clock_out_error()
    {
        $response = $this->actingAs($this->admin)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['start' => '17:00', 'end' => '19:00']],
            'remarks' => '管理者による修正',
        ]);

        $response->assertSessionHasErrors([
            'rests.0.end' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 5. 備考欄が未入力の場合のエラー
     */
    public function test_admin_remarks_is_required_error()
    {
        $response = $this->actingAs($this->admin)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => '',
        ]);

        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください'
        ]);
    }
}
