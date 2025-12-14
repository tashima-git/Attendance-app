<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_own_attendance_displayed()
    {
        $user = User::factory()->create();
        Attendance::factory()->count(3)->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->get('/attendance/list');
        foreach ($user->attendances as $attendance) {
            $response->assertSee($attendance->clock_in);
        }
    }

    public function test_current_month_displayed()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get('/attendance/list');
        $response->assertSee(now()->format('Y-m'));
    }

    public function test_previous_month_displayed_when_button_pressed()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $prevMonth = now()->subMonth()->format('Y-m');
        $response = $this->get('/attendance/list?month=prev');
        $response->assertSee($prevMonth);
    }

    public function test_next_month_displayed_when_button_pressed()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $nextMonth = now()->addMonth()->format('Y-m');
        $response = $this->get('/attendance/list?month=next');
        $response->assertSee($nextMonth);
    }

    public function test_detail_button_redirects_to_attendance_detail()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->get("/attendance/list/{$attendance->id}/detail");
        $response->assertSee($attendance->clock_in);
    }
}
