<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ① 休憩ボタンが正しく機能する
     * 「休憩入」ボタンが表示され、処理後にステータスが「休憩中」になる
     */
    public function 休憩ボタンが正しく機能する()
    {
        Carbon::setTestNow('2025-12-19 09:00:00');

        $user = User::factory()->create();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in'  => now()->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // 出勤中 → 休憩入ボタンが表示
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩開始
        $this->post(route('attendance.store'), ['break_start' => true]);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * ② 休憩は一日に何回でもできる
     */
    public function 休憩は一日に何回でもできる()
    {
        Carbon::setTestNow('2025-12-19 09:00:00');

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // 1回目休憩
        $this->post(route('attendance.store'), ['break_start' => true]);
        $this->post(route('attendance.store'), ['break_end' => true]);

        // 2回目休憩
        $this->post(route('attendance.store'), ['break_start' => true]);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩中');

        $this->assertCount(2, BreakTime::all());
    }

    /**
     * ③ 休憩戻ボタンが正しく機能する
     */
    public function 休憩戻ボタンが正しく機能する()
    {
        Carbon::setTestNow('2025-12-19 12:00:00');

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(3)->format('H:i:s'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // 休憩戻
        $this->post(route('attendance.store'), ['break_end' => true]);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('出勤中');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);
    }

    /**
     * ④ 休憩戻は一日に何回でもできる
     */
    public function 休憩戻は一日に何回でもできる()
    {
        Carbon::setTestNow('2025-12-19 12:00:00');

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(3)->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // 1回目休憩
        $this->post(route('attendance.store'), ['break_start' => true]);
        $this->post(route('attendance.store'), ['break_end' => true]);

        // 2回目休憩
        $this->post(route('attendance.store'), ['break_start' => true]);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩中');

        $this->assertCount(2, BreakTime::all());
    }

    /**
     * ⑤ 休憩時刻が勤怠一覧画面で確認できる
     */
    public function 休憩時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow('2025-12-19 09:00:00');

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9,0)->format('H:i:s'),
            'clock_out' => now()->setTime(18,0)->format('H:i:s'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->setTime(12,0)->format('H:i:s'),
            'break_end' => now()->setTime(13,0)->format('H:i:s'),
            'total_break_time' => 60,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('01:00'); // 休憩時間表示
    }
}
