<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakTime;
use App\Models\Attendance;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'break_start' => '12:00',
            'break_end' => '12:30',
            'total_break_time' => 30,
        ];
    }
}
