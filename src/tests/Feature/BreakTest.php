<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_break_in_button_works()
    {
        $user = User::factory()->create(['attendance_status' => 'working']);
        $this->actingAs($user);

        $response = $this->post('/attendance/break-in');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'break_start' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_break_out_button_works()
    {
        $user = User::factory()->create(['attendance_status' => 'on_break']);
        $this->actingAs($user);

        $response = $this->post('/attendance/break-out');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'break_end' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_break_can_be_done_multiple_times()
    {
        $user = User::factory()->create(['attendance_status' => 'working']);
        $this->actingAs($user);

        $this->post('/attendance/break-in');
        $this->post('/attendance/break-out');
        $this->post('/attendance/break-in');

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    public function test_break_time_recorded_in_attendance_list()
    {
        $user = User::factory()->create(['attendance_status' => 'working']);
        $this->actingAs($user);

        $this->post('/attendance/break-in');
        $this->post('/attendance/break-out');

        $response = $this->get('/attendance/list');
        $response->assertSee(now()->format('H:i'));
    }
}
