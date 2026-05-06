<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 会員登録後、認証メールが送信される
     */
    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 登録したユーザーに認証メール（VerifyEmail通知）が送信されているか確認
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 2. メール認証誘導画面で「認証はこちらから」ボタン（MailHog等）のリンクがあるか確認
     */
    public function test_verification_notice_screen_has_link_to_mail_client()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        // ブレードに記載されていたメール認証サイト(MailHog)へのリンクを確認
        $response->assertSee('http://localhost:8025');
    }

    /**
     * 3. メール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_email_can_be_verified_and_redirects_to_attendance()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // Laravelが生成する署名付き認証URLを模倣
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // 認証完了後、勤怠登録画面（/attendance）へリダイレクトされるか確認
        $response->assertRedirect('/attendance');

        // 実際にDBのemail_verified_atが更新されているか確認
        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
