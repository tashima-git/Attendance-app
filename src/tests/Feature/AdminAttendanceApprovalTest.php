<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;

class AdminAttendanceApprovalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 承認待ちの修正申請が全て表示されている()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $request = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'clock_in' => '10:00:00',
            'clock_out' => '18:00:00',
            'remarks' => '遅刻修正テスト',
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/correction-request/list?status=pending');
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('遅刻修正テスト');
    }

    /** @test */
    public function 承認済みの修正申請が全て表示されている()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $request = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'remarks' => '早退修正テスト',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => Carbon::now(),
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/correction-request/list?status=approved');
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('早退修正テスト');
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示されている()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $request = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'clock_in' => '10:30:00',
            'clock_out' => '18:30:00',
            'remarks' => '詳細確認テスト',
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get("/admin/correction-request/detail/{$request->id}");
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('10:30');
        $response->assertSee('18:30');
        $response->assertSee('詳細確認テスト');
    }

    /** @test */
    public function 修正申請の承認処理が正しく行われる()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $request = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'clock_in' => '10:00:00',
            'clock_out' => '18:00:00',
            'remarks' => '承認テスト',
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin');

        // 承認アクション
        $response = $this->post("/admin/correction-request/approve/{$request->id}");
        $response->assertStatus(302); // リダイレクトされる想定

        $request->refresh();
        $attendance->refresh();

        // 承認済みに更新されていること
        $this->assertEquals('approved', $request->status);
        $this->assertEquals($admin->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);

        // 勤怠情報が修正申請通りに更新されていること
        $this->assertEquals('10:00:00', $attendance->clock_in);
        $this->assertEquals('18:00:00', $attendance->clock_out);
    }
}
