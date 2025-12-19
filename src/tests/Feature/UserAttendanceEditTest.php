<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\CorrectionBreakTime;
use Carbon\Carbon;

class UserAttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合エラーが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post(route('correction_request.store'), [
            'attendance_id' => $attendance->id,
            'work_date'     => Carbon::today()->format('Y-m-d'),
            'clock_in'      => '18:00',
            'clock_out'     => '09:00',
            'remarks'       => 'テスト',
        ]);

        $response->assertSessionHasErrors(['clock_in']);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合エラーが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post(route('correction_request.store'), [
            'attendance_id' => $attendance->id,
            'work_date'     => Carbon::today()->format('Y-m-d'),
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'remarks'       => 'テスト',
            'breaks'        => [
                ['break_start' => '19:00', 'break_end' => '19:30']
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_start']);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合エラーが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post(route('correction_request.store'), [
            'attendance_id' => $attendance->id,
            'work_date'     => Carbon::today()->format('Y-m-d'),
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'remarks'       => 'テスト',
            'breaks'        => [
                ['break_start' => '12:00', 'break_end' => '19:00']
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_end']);
    }

    /** @test */
    public function 備考欄が未入力の場合エラーが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post(route('correction_request.store'), [
            'attendance_id' => $attendance->id,
            'work_date'     => Carbon::today()->format('Y-m-d'),
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'remarks'       => '',
        ]);

        $response->assertSessionHasErrors(['remarks']);
    }

    /** @test */
    public function 修正申請処理が正常に実行される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post(route('correction_request.store'), [
            'attendance_id' => $attendance->id,
            'work_date'     => Carbon::today()->format('Y-m-d'),
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'remarks'       => '修正申請テスト',
            'breaks'        => [
                ['break_start' => '12:00', 'break_end' => '13:00']
            ],
        ]);

        $response->assertRedirect(route('correction_request.index', ['status' => 'pending']));

        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'remarks'       => '修正申請テスト',
            'status'        => 'pending',
        ]);

        $this->assertDatabaseHas('correction_break_times', [
            'break_start' => '12:00',
            'break_end'   => '13:00',
        ]);
    }

    /** @test */
    public function 承認待ちに自分の申請が全て表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        AttendanceCorrectionRequest::factory()->create([
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'remarks'       => '承認待ち申請',
            'status'        => 'pending',
        ]);

        $response = $this->get(route('correction_request.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('承認待ち申請');
    }

    /** @test */
    public function 承認済みに管理者が承認した申請が全て表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        AttendanceCorrectionRequest::factory()->create([
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'remarks'       => '承認済み申請',
            'status'        => 'approved',
        ]);

        $response = $this->get(route('correction_request.index', ['status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済み申請');
    }

    /** @test */
    public function 各申請の詳細を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $request = AttendanceCorrectionRequest::factory()->create([
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'remarks'       => '詳細確認申請',
        ]);

        $response = $this->get(route('correction_request.show', ['id' => $request->id]));

        $response->assertStatus(200);
        $response->assertSee($attendance->work_date);
    }
}
