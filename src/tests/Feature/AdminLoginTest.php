<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpass123'),
            'is_admin' => true,
        ]);
    }

    public function test_email_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'adminpass123',
        ]);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_password_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_login_with_invalid_credentials()
    {
        $response = $this->post('/admin/login', [
            'email' => 'wrongadmin@example.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    public function test_successful_admin_login()
    {
        $response = $this->post('/admin/login', [
            'email' => $this->admin->email,
            'password' => 'adminpass123',
        ]);
        $response->assertRedirect('/admin/home');
        $this->assertAuthenticatedAs($this->admin);
    }
}
