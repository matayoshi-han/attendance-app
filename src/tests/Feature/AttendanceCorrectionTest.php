<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => '2024-10-15',
            'clock_in' => '2024-10-15 09:00:00',
            'clock_out' => '2024-10-15 18:00:00',
        ]);
    }

    /**
     * 1. 出勤時間が退勤時間より後の場合
     */
    public function test_clock_in_after_clock_out_error()
    {
        $response = $this->actingAs($this->user)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'remarks' => 'テスト備考',
        ]);

        // リクエストクラスのメッセージと完全に一致させる
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 2. 休憩開始時間が退勤時間より後の場合
     */
    public function test_break_start_after_clock_out_error()
    {
        $response = $this->actingAs($this->user)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['start' => '19:00', 'end' => '20:00']],
            'remarks' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'rests.0.start' => '休憩時間が不適切な値です'
        ]);
    }

    /**
     * 3. 備考欄が未入力の場合
     */
    public function test_remarks_is_required_error()
    {
        $response = $this->actingAs($this->user)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => '',
        ]);

        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください'
        ]);
    }

    /**
     * 4. 修正申請処理の正常系ワークフロー
     */
    public function test_correction_request_workflow()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->user)->post(route('attendance.correction.store', $this->attendance->id), [
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'remarks' => '時間修正依頼',
        ]);

        $response = $this->get(route('correction.list', ['tab' => 'pending']));
        $response->assertSee('時間修正依頼');

        $correction = AttendanceCorrection::first();
        $this->actingAs($admin)->post(route('attendance.approve', $correction->id));

        $response = $this->actingAs($this->user)->get(route('correction.list', ['tab' => 'approved']));
        $response->assertSee('承認済み');
    }
}
