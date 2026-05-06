<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceDetailDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面の各項目が正しく表示されているか確認
     */
    public function test_attendance_detail_display_is_correct()
    {
        // テストデータの準備
        $user = User::factory()->create(['name' => 'テスト太郎']);
        $date = Carbon::create(2024, 10, 15);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'),
            'clock_out' => $date->copy()->setTime(18, 0, 0)->format('Y-m-d H:i:s'),
        ]);

        // 休憩データの作成
        Rest::create([
            'attendance_id' => $attendance->id,
            'break_start' => $date->copy()->setTime(12, 0, 0)->format('Y-m-d H:i:s'),
            'break_end' => $date->copy()->setTime(13, 0, 0)->format('Y-m-d H:i:s'),
        ]);

        // 1. ログインして勤怠詳細ページを開く
        $response = $this->actingAs($user)->get(route('attendance.show', $attendance->id));
        $response->assertStatus(200);

        // 2. 名前欄を確認する
        $response->assertSee('テスト太郎');

        // 3. 日付欄を確認する (ブレードの形式：2024年10月15日)
        $response->assertSee('2024年');
        $response->assertSee('10月15日');

        // 4. 出勤・退勤欄を確認する (09:00, 18:00)
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 5. 休憩欄を確認する (12:00, 13:00)
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
