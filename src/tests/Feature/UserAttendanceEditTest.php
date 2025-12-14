<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class UserAttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_clock_in_after_clock_out_shows_error()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->put("/attendance/{$attendance->id}", [
            'clock_in' => now()->addHours(9)->format('H:i'),
            'clock_out' => now()->format('H:i'),
            'remarks' => 'Test',
        ]);
        $response->assertSessionHasErrors(['clock_in']);
    }

    public function test_empty_remarks_shows_error()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->put("/attendance/{$attendance->id}", [
            'clock_in' => now()->format('H:i'),
            'clock_out' => now()->addHours(8)->format('H:i'),
            'remarks' => '',
        ]);
        $response->assertSessionHasErrors(['remarks']);
    }

    public function test_edit_request_is_submitted_successfully()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->put("/attendance/{$attendance->id}", [
            'clock_in' => now()->format('H:i'),
            'clock_out' => now()->addHours(8)->format('H:i'),
            'remarks' => '修正申請テスト',
        ]);
        $response->assertRedirect('/attendance/list');
        $this->assertDatabaseHas('attendance_edits', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'remarks' => '修正申請テスト',
        ]);
    }
}
