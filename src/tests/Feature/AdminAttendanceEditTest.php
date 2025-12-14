<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceEdit;

class AdminAttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_edits_are_displayed()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $edit = AttendanceEdit::factory()->create(['status' => 'pending']);
        $this->actingAs($admin);

        $response = $this->get('/admin/attendance/edits?status=pending');
        $response->assertSee($edit->remarks);
    }

    public function test_approved_edits_are_displayed()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $edit = AttendanceEdit::factory()->create(['status' => 'approved']);
        $this->actingAs($admin);

        $response = $this->get('/admin/attendance/edits?status=approved');
        $response->assertSee($edit->remarks);
    }

    public function test_approval_process_updates_attendance()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $edit = AttendanceEdit::factory()->create(['status' => 'pending']);
        $this->actingAs($admin);

        $response = $this->post("/admin/attendance/edits/{$edit->id}/approve");
        $response->assertRedirect('/admin/attendance/edits');
        $this->assertDatabaseHas('attendance_edits', ['id' => $edit->id, 'status' => 'approved']);
    }
}
