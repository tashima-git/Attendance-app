<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_users_attendance_displayed()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            Attendance::factory()->create(['user_id' => $user->id]);
        }
        $this->actingAs($admin);

        $response = $this->get('/admin/attendance/list');
        foreach ($users as $user) {
            $response->assertSee($user->name);
        }
    }

    public function test_current_day_displayed()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->get('/admin/attendance/list');
        $response->assertSee(now()->format('Y-m-d'));
    }

    public function test_previous_day_displayed()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $prevDay = now()->subDay()->format('Y-m-d');
        $response = $this->get('/admin/attendance/list?day=prev');
        $response->assertSee($prevDay);
    }

    public function test_next_day_displayed()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $nextDay = now()->addDay()->format('Y-m-d');
        $response = $this->get('/admin/attendance/list?day=next');
        $response->assertSee($nextDay);
    }
}
