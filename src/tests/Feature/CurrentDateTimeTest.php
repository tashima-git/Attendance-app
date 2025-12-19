<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;

class CurrentDatetimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時が画面に正しく表示される
     */
    public function 現在の日時が画面に正しく表示される()
    {
        // 認証（attendance はログイン必須）
        $user = User::factory()->create();
        $this->actingAs($user);

        // 時刻を固定
        Carbon::setTestNow(Carbon::create(2025, 12, 19, 9, 0));

        $response = $this->get('/attendance');

        // UIと同じ形式で期待値を作成
        $expectedDate = Carbon::now()->isoFormat('YYYY年MM月DD日(ddd)');
        $expectedTime = Carbon::now()->format('H:i');

        $response->assertStatus(200);
        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);

        Carbon::setTestNow();
    }
}
