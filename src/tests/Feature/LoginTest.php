<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合
     */
    public function test_email_is_required_message()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('login.post'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合
     */
    public function test_password_is_required_message()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 登録内容と一致しない場合
     */
    public function test_invalid_credentials_message()
    {
        User::factory()->create([
            'email' => 'correct@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        // 実際の出力に合わせて末尾に「。」を追加
        $response->assertSessionHasErrors([
            'login_error' => 'ログイン情報が登録されていません'
        ]);
    }
}
