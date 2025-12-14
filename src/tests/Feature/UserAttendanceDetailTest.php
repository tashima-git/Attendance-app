<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_and_date_displayed_correctly()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->get("/attendance/{$attendance->id}/detail");
        $response->assertSee($user->name);
        $response->assertSee($attendance->date->format('Y-m-d'));
    }

    public function test_clock_and_break_times_displayed_correctly()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => now()->format('Y-m-d H:i:s'),
            'clock_out' => now()->addHours(8)->format('Y-m-d H:i:s'),
            'break_start' => now()->addHours(4)->format('Y-m-d H:i:s'),
            'break_end' => now()->addHours(5)->format('Y-m-d H:i:s'),
        ]);
        $this->actingAs($user);

        $response = $this->get("/attendance/{$attendance->id}/detail");
        $response->assertSee($attendance->clock_in);
        $response->assertSee($attendance->clock_out);
        $response->assertSee($attendance->break_start);
        $response->assertSee($attendance->break_end);
    }
}
