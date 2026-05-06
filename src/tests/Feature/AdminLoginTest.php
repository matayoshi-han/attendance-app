<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合
     */
    public function test_admin_email_is_required_message()
    {
        // 1. ユーザーを登録する（管理者権限）
        User::factory()->create(['email' => 'admin@example.com', 'role' => 'admin']);

        // 2. メールアドレス以外の情報を入力し、3. ログイン処理
        $response = $this->post(route('admin.login.post'), [
            'email' => '',
            'password' => 'password123',
        ]);

        // バリデーションメッセージの検証（一般ユーザー側に合わせた文言）
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合
     */
    public function test_admin_password_is_required_message()
    {
        User::factory()->create(['email' => 'admin@example.com', 'role' => 'admin']);

        $response = $this->post(route('admin.login.post'), [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 登録内容と一致しない場合
     */
    public function test_admin_invalid_credentials_message()
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin'
        ]);

        $response = $this->post(route('admin.login.post'), [
            'email' => 'wrong-admin@example.com',
            'password' => 'password123',
        ]);

        // 前回の結果に基づき、末尾に「。」を追加
        $response->assertSessionHasErrors([
            'login_error' => 'ログイン情報が登録されていません'
        ]);
    }
}
