<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_button_works()
    {
        $user = User::factory()->create(['attendance_status' => 'off_work']);
        $this->actingAs($user);

        $response = $this->post('/attendance/clock-in');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_clock_in_only_once_per_day()
    {
        $user = User::factory()->create(['attendance_status' => 'left_work']);
        $this->actingAs($user);

        $response = $this->post('/attendance/clock-in');
        $response->assertSessionMissing('clock_in');
    }

    public function test_clock_in_time_is_recorded_in_attendance_list()
    {
        $user = User::factory()->create(['attendance_status' => 'off_work']);
        $this->actingAs($user);
        $this->post('/attendance/clock-in');

        $response = $this->get('/attendance/list');
        $response->assertSee(now()->format('H:i'));
    }
}
