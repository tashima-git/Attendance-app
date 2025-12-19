<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面の名前がログインユーザーの氏名になっている()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.show', ['id' => $attendance->work_date]));

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /** @test */
    public function 勤怠詳細画面の日付が選択した日付になっている()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::create(2025, 12, 19),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.show', ['id' => $attendance->work_date]));

        $response->assertStatus(200);
        $response->assertSee('2025-12-19');
    }

    /** @test */
    public function 出勤退勤時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.show', ['id' => $attendance->work_date]));

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 休憩時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_id'   => $attendance->id,
            'break_start'     => Carbon::today()->setTime(12, 0),
            'break_end'       => Carbon::today()->setTime(12, 30),
            'total_break_time'=> 30,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.show', ['id' => $attendance->work_date]));

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('12:30');
    }
}
