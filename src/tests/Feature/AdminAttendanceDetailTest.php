<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_data_matches_selected_attendance()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin);
        $response = $this->get("/admin/attendance/{$attendance->id}/detail");

        $response->assertSee($user->name);
        $response->assertSee($attendance->clock_in);
    }

    public function test_invalid_clock_in_after_clock_out_shows_error()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($admin);

        $response = $this->put("/admin/attendance/{$attendance->id}", [
            'clock_in' => now()->addHours(9)->format('H:i'),
            'clock_out' => now()->format('H:i'),
            'remarks' => '修正テスト',
        ]);
        $response->assertSessionHasErrors(['clock_in']);
    }

    public function test_empty_remarks_shows_error()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($admin);

        $response = $this->put("/admin/attendance/{$attendance->id}", [
            'clock_in' => now()->format('H:i'),
            'clock_out' => now()->addHours(8)->format('H:i'),
            'remarks' => '',
        ]);
        $response->assertSessionHasErrors(['remarks']);
    }
}
