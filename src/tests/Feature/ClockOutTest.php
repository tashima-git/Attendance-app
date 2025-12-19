<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ① 退勤ボタンが正しく機能する
     * 画面に「退勤」ボタンが表示され、処理後にステータスが「退勤済」になる
     */
    public function 退勤ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::today()->setTime(18, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->setTime(9, 0)->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // 勤務中 → 退勤ボタンが表示
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('退勤');

        // 退勤処理
        $this->post(route('attendance.store'), ['clock_out' => true])
             ->assertStatus(302); // リダイレクト確認

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('退勤済');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_out' => Carbon::now()->format('H:i:s'),
        ]);
    }

    /**
     * ② 退勤時刻が勤怠一覧画面で確認できる
     */
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::today()->setTime(18, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->setTime(9, 0)->format('H:i:s'),
        ]);

        $this->actingAs($user);

        // 退勤処理
        $this->post(route('attendance.store'), ['clock_out' => true]);

        // 勤怠一覧画面で時刻を確認
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('18:00');
    }
}
