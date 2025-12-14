<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_off_work()
    {
        $user = User::factory()->create(['attendance_status' => 'off_work']);
        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('勤務外');
    }

    public function test_status_working()
    {
        $user = User::factory()->create(['attendance_status' => 'working']);
        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    public function test_status_on_break()
    {
        $user = User::factory()->create(['attendance_status' => 'on_break']);
        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    public function test_status_left_work()
    {
        $user = User::factory()->create(['attendance_status' => 'left_work']);
        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }
}
