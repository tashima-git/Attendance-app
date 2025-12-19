<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        // ユーザー作成
        $users = User::factory()->count(3)->create();

        // 各ユーザーに今日の勤怠情報を作成
        foreach ($users as $user) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => Carbon::today(),
            ]);
        }

        // 管理者でログイン（adminガード）
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list');

        foreach ($users as $user) {
            $response->assertSee($user->name);
        }
    }

    /** @test */
    public function 遷移した際に現在の日付が表示される()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        // Blade 表示形式に合わせる
        $today = Carbon::today()->format('Y年n月j日');

        $response = $this->get('/admin/attendance/list');

        $response->assertSee($today);
    }

    /** @test */
    public function 前日を押下した時に前の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();
        $prevDayCarbon = Carbon::yesterday();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $prevDayCarbon,
        ]);

        $response = $this->get('/admin/attendance/list?day=prev');

        $prevDay = $prevDayCarbon->format('Y年n月j日');
        $response->assertSee($prevDay);
        $response->assertSee($user->name);
    }

    /** @test */
    public function 翌日を押下した時に次の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();
        $nextDayCarbon = Carbon::tomorrow();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $nextDayCarbon,
        ]);

        $response = $this->get('/admin/attendance/list?day=next');

        $nextDay = $nextDayCarbon->format('Y年n月j日');
        $response->assertSee($nextDay);
        $response->assertSee($user->name);
    }
}
