<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_out_button_works()
    {
        $user = User::factory()->create(['attendance_status' => 'working']);
        $this->actingAs($user);

        $response = $this->post('/attendance/clock-out');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_out' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_clock_out_time_recorded_in_attendance_list()
    {
        $user = User::factory()->create(['attendance_status' => 'working']);
        $this->actingAs($user);

        $this->post('/attendance/clock-in');
        $this->post('/attendance/clock-out');

        $response = $this->get('/attendance/list');
        $response->assertSee(now()->format('H:i'));
    }
}
