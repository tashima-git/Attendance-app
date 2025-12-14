<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;
use App\Models\User;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_sent_after_registration()
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => null]);

        Notification::assertNothingSent();
        $user->sendEmailVerificationNotification();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_link_redirects_to_attendance_page()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $response = $this->get('/email/verify');
        $response->assertSee('認証はこちらから');
    }

    public function test_user_can_access_attendance_after_verification()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
    }
}
