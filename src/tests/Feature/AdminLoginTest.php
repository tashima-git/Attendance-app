<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者かどうかはこのテストでは関係ないため
        // usersテーブルに存在するカラムのみ指定する
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpass123'),
        ]);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'adminpass123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals(
            'パスワードを入力してください',
            session('errors')->first('password')
        );
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_登録内容と一致しない場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/admin/login', [
            'email' => 'wrongadmin@example.com',
            'password' => 'adminpass123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );
    }
}
