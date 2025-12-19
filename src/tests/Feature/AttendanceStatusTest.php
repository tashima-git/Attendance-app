<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤務外の場合、ステータスが「勤務外」と表示される
     */
    public function test_勤務外の場合_ステータスが表示される()
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、ステータスが「出勤中」と表示される
     */
    public function test_出勤中の場合_ステータスが表示される()
    {
        $user = User::factory()->create();

        // 今日の出勤データ
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in' => now()->subHours(1)->format('H:i:s'),
            'clock_out' => null,
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、ステータスが「休憩中」と表示される
     */
    public function test_休憩中の場合_ステータスが表示される()
    {
        $user = User::factory()->create();

        // 今日の出勤データ
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in' => now()->subHours(2)->format('H:i:s'),
            'clock_out' => null,
        ]);

        // 休憩開始済み
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subMinutes(30)->format('H:i:s'),
            'break_end' => null,
            'total_break_time' => null,
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、ステータスが「退勤済」と表示される
     */
    public function test_退勤済の場合_ステータスが表示される()
    {
        $user = User::factory()->create();

        // 今日の出勤・退勤データ
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in' => now()->subHours(8)->format('H:i:s'),
            'clock_out' => now()->format('H:i:s'),
        ]);

        // 休憩データ（任意、あっても良い）
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subHours(4)->format('H:i:s'),
            'break_end' => now()->subHours(3, 30)->format('H:i:s'),
            'total_break_time' => 30,
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
