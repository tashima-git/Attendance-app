<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminUserInfoTest extends TestCase
{
    use RefreshDatabase;

/** @test */
    public function 管理者が全一般ユーザーの氏名とメールアドレスを確認できる()
        {
            $admin = Admin::factory()->create();
            $users = User::factory()->count(3)->create();

            $this->actingAs($admin, 'admin');

            $response = $this->get('/admin/staff/list');

            foreach ($users as $user) {
                $response->assertSeeText($user->name);
                $response->assertSeeText($user->email);
            }
        }

    /** @test */
    public function 選択したユーザーの勤怠情報が正確に表示される()
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

        // ルート修正
        $response = $this->get("/admin/attendance/staff/{$user->id}?month=" . Carbon::now()->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 前月ボタンを押すと前月の情報が表示される()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin');

        $prevMonth = Carbon::now()->subMonth()->format('Y-m');

        $response = $this->get("/admin/attendance/staff/{$user->id}?month={$prevMonth}");

        $response->assertStatus(200);
        $response->assertSee($prevMonth);
    }

    /** @test */
    public function 翌月ボタンを押すと翌月の情報が表示される()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin');

        $nextMonth = Carbon::now()->addMonth()->format('Y-m');

        $response = $this->get("/admin/attendance/staff/{$user->id}?month={$nextMonth}");

        $response->assertStatus(200);
        $response->assertSee($nextMonth);
    }

    /** @test */
    public function 勤怠一覧の詳細ボタンを押すとその日の勤怠詳細画面に遷移する()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get("/admin/attendance/staff/{$user->id}?month=" . Carbon::now()->format('Y-m'));

        // 詳細ボタンのリンク先を確認
        $response->assertSee("/admin/attendance/{$attendance->id}?date={$attendance->work_date->format('Y-m-d')}");
    }
}
