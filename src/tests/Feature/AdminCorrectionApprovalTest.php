<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdminCorrectionApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['name' => '申請スタッフ']);
        $this->date = '2024-10-15';
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => $this->date,
            'clock_in' => $this->date . ' 09:00:00',
            'clock_out' => $this->date . ' 18:00:00',
        ]);
    }

    /**
     * 1. 承認待ちの修正申請が全て表示されている
     */
    public function test_admin_can_view_all_pending_corrections()
    {
        AttendanceCorrection::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'updated_clock_in' => $this->date . ' 08:30:00',
            'updated_clock_out' => $this->date . ' 17:30:00',
            'remarks' => '打刻ミス修正願い',
            'status' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('correction.list', ['tab' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('申請スタッフ');
        $response->assertSee('打刻ミス修正願い');
    }

    /**
     * 2. 承認済みの修正申請が全て表示されている
     */
    public function test_admin_can_view_all_approved_corrections()
    {
        AttendanceCorrection::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'updated_clock_in' => $this->date . ' 08:30:00',
            'updated_clock_out' => $this->date . ' 17:30:00',
            'remarks' => '承認済みの申請',
            'status' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('correction.list', ['tab' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('承認済みの申請');
    }

    /**
     * 3. 修正申請の詳細内容が正しく表示されている
     */
    public function test_admin_can_view_correction_detail_content()
    {
        AttendanceCorrection::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'updated_clock_in' => $this->date . ' 08:45:00',
            'updated_clock_out' => $this->date . ' 17:45:00',
            'remarks' => '詳細確認用',
            'status' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        $response->assertSee('08:45');
        $response->assertSee('17:45');
        $response->assertSee('詳細確認用');
    }

    /**
     * 4. 管理者が勤怠詳細を直接修正すると、即座に勤怠データが更新される
     */
    public function test_admin_can_directly_update_attendance_data()
    {
        $response = $this->actingAs($this->admin)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'remarks' => '管理者による直接修正',
        ]);

        // 直接 attendances テーブルが更新されているか確認
        $this->attendance->refresh();
        $this->assertEquals('08:00:00', date('H:i:s', strtotime($this->attendance->clock_in)));
        $this->assertEquals('17:00:00', date('H:i:s', strtotime($this->attendance->clock_out)));

        $response->assertSessionHas('success', '勤怠データを更新しました。');
    }

    /**
     * 5. 一般ユーザーが修正申請を行い、管理者がそれを承認するとデータが反映される
     */
    public function test_user_correction_request_and_admin_approval()
    {
        // 1. 一般ユーザーで申請
        $this->actingAs($this->user)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '07:00',
            'clock_out' => '16:00',
            'remarks' => '一般ユーザーの申請',
        ]);

        $correction = AttendanceCorrection::where('user_id', $this->user->id)->first();
        $this->assertEquals(0, $correction->status); // 承認待ち

        // 2. 管理者で承認処理を実行
        // コントローラーのロジックに合わせ、action => approve を送信する
        $this->actingAs($this->admin)->post(route('attendance.approve', $correction->id), [
            'action' => 'approve'
        ]);

        // 3. 承認後のステータス(1)と、本体の勤怠時刻が更新されたか確認
        $this->assertEquals(1, $correction->refresh()->status);

        $this->attendance->refresh();
        $this->assertEquals('07:00:00', date('H:i:s', strtotime($this->attendance->clock_in)));
        $this->assertEquals('16:00:00', date('H:i:s', strtotime($this->attendance->clock_out)));
    }
}
