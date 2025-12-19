<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤処理
        $this->post('/attendance', ['clock_in' => true]);

        // DBに勤怠レコードが作成されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
        ]);

        // ステータスが勤務中になることを確認
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 出勤は一日一回のみできる()
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $user = User::factory()->create();
        $this->actingAs($user);

        // 初回出勤
        $this->post('/attendance', ['clock_in' => true]);

        // 再度出勤（DBに重複が作られていないか確認）
        $this->post('/attendance', ['clock_in' => true]);

        // DB 上で今日の出勤レコードが1件だけであることを確認
        $this->assertCount(
            1,
            Attendance::where('user_id', $user->id)
                      ->whereDate('work_date', Carbon::today())
                      ->get()
        );
    }

    /** @test */
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤処理
        $this->post('/attendance', ['clock_in' => true]);

        // 勤怠一覧画面を確認
        $response = $this->get('/attendance/list');

        // ページに 09:00 が表示されるか確認
        $response->assertSee('09:00');
    }
}
