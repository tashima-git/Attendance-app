<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 自分の勤怠情報が全て表示されている()
    {
        Carbon::setTestNow(Carbon::today());

        $user = User::factory()->create();
        $other = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->setTime(9, 0),
        ]);

        Attendance::factory()->create([
            'user_id'   => $other->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->setTime(10, 0),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.list'));

        $response->assertSee('09:00');
        $response->assertDontSee('10:00');
    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 15));

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('attendance.list'));

        $response->assertSee('2025-01');
    }

    /** @test */
    public function 前月を押下した時に前月の情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 15));

        $user = User::factory()->create();
        $this->actingAs($user);

        // 前月 (2024-12) の勤怠を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2024, 12, 20),
            'clock_in' => Carbon::create(2024, 12, 9, 9, 0),
        ]);

        $response = $this->get(route('attendance.list', ['month' => '2024-12']));

        $response->assertStatus(200);
        $response->assertSee('2024-12');
        $response->assertSee('09:00');
    }

    /** @test */
    public function 翌月を押下した時に翌月の情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 15));

        $user = User::factory()->create();
        $this->actingAs($user);

        // 翌月 (2025-02) の勤怠を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2025, 2, 10),
            'clock_in' => Carbon::create(2025, 2, 9, 10, 0),
        ]);

        $response = $this->get(route('attendance.list', ['month' => '2025-02']));

        $response->assertStatus(200);
        $response->assertSee('2025-02');
        $response->assertSee('10:00');
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        Carbon::setTestNow(Carbon::today());

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->setTime(9, 0),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.show', ['id' => $attendance->work_date]));

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee($user->name);
    }
}
