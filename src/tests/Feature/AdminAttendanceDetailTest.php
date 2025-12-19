<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものと一致する()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'remarks'   => '通常勤務',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get("/admin/attendance/{$attendance->id}?date={$attendance->work_date->format('Y-m-d')}");

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('通常勤務');
    }

    /** @test */
    public function 出勤時間が退勤時間より後の場合はエラーメッセージが表示される()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->put("/admin/attendance/{$attendance->id}", [
            'work_date' => $attendance->work_date->toDateString(),
            'clock_in'  => '18:00',
            'clock_out' => '09:00',
            'remarks'   => '修正テスト',
            'breaks'    => [],
        ]);

        $response->assertSessionHasErrors(['clock_in']);
    }

    /** @test */
    public function 備考欄が未入力の場合はエラーメッセージが表示される()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->put("/admin/attendance/{$attendance->id}", [
            'work_date' => $attendance->work_date->toDateString(),
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'remarks'   => '',
            'breaks'    => [],
        ]);

        $response->assertSessionHasErrors(['remarks']);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後の場合はエラーメッセージが表示される()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->put("/admin/attendance/{$attendance->id}", [
            'work_date' => $attendance->work_date->toDateString(),
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'remarks'   => 'テスト',
            'breaks' => [
                ['break_start' => '19:00', 'break_end' => '19:30']
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_start']);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後の場合はエラーメッセージが表示される()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->put("/admin/attendance/{$attendance->id}", [
            'work_date' => $attendance->work_date->toDateString(),
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'remarks'   => 'テスト',
            'breaks' => [
                ['break_start' => '12:00', 'break_end' => '19:00']
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_end']);
    }
}
