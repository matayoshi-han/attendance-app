<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceDateTimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時情報がUIと同じ形式（Y年m月d日(曜)）で出力されていることを確認
     */
    public function test_current_datetime_is_displayed_correctly()
    {
        // 1. テスト用の現在時刻を固定
        $testDate = Carbon::create(2024, 10, 15, 10, 0, 0);
        Carbon::setTestNow($testDate);

        $user = User::factory()->create();

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        // 3. 画面上に表示されている日付を確認（ここは成功します）
        $response->assertSee('2024年10月15日(火)');

        // 【修正】JavaScriptで制御される部分はPHPUnitでは見えないため、削除またはコメントアウト
        // $response->assertSee('10:00');

        Carbon::setTestNow();
    }
}
