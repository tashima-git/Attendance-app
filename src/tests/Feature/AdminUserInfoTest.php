<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AdminUserInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_general_users_displayed_with_name_and_email()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $users = User::factory()->count(3)->create(['is_admin' => false]);

        $this->actingAs($admin);
        $response = $this->get('/admin/users');

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    public function test_user_attendance_list_displayed_correctly()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $this->actingAs($admin);

        $response = $this->get("/admin/users/{$user->id}/attendance");
        $response->assertSee($user->name);
    }

    public function test_previous_and_next_month_buttons_work()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $this->actingAs($admin);

        $prevMonth = now()->subMonth()->format('Y-m');
        $nextMonth = now()->addMonth()->format('Y-m');

        $responsePrev = $this->get("/admin/users/{$user->id}/attendance?month=prev");
        $responsePrev->assertSee($prevMonth);

        $responseNext = $this->get("/admin/users/{$user->id}/attendance?month=next");
        $responseNext->assertSee($nextMonth);
    }
}
