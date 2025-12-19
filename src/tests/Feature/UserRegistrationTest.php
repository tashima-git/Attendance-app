<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 名前が未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(
            'お名前を入力してください',
            session('errors')->first('name')
        );
    }

    /** @test */
    public function メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );
    }

    /** @test */
    public function パスワードが8文字未満の場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals(
            'パスワードは8文字以上で入力してください',
            session('errors')->first('password')
        );
    }

    /** @test */
    public function パスワードが一致しない場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password321',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals(
            'パスワードと一致しません',
            session('errors')->first('password')
        );
    }

    /** @test */
    public function パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals(
            'パスワードを入力してください',
            session('errors')->first('password')
        );
    }

    /** @test */
    public function フォームに内容が入力されていた場合_データが正常に保存される()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // DBにユーザーが保存されていることを確認
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);

        // 登録後はリダイレクト（例: /home）
        $response->assertRedirect('/home');
    }
}
