<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        // 出勤時間を 8:00〜10:00 の間でランダム
        $clockIn = Carbon::createFromTime(rand(8, 10), rand(0, 59));

        // 退勤時間を出勤時間 + 7〜9時間
        $clockOut = $clockIn->copy()->addHours(rand(7, 9))->addMinutes(rand(0, 59));

        // 休憩を1〜3回ランダム生成
        $breaks = [];
        $numBreaks = rand(1, 3);
        for ($i = 0; $i < $numBreaks; $i++) {
            $start = $clockIn->copy()->addHours(rand(1, 5))->addMinutes(rand(0, 30));
            $end = $start->copy()->addMinutes(rand(30, 60));
            $breaks[] = [
                'break_start' => $start->format('H:i:s'),
                'break_end' => $end->format('H:i:s')
            ];
        }

        return [
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
            'work_date' => now()->format('Y-m-d'),
        ];
    }

    /**
     * カスタムメソッドでユーザーIDと日付を指定
     */
    public function forUserAndDate($userId, $date)
    {
        return $this->state(function (array $attributes) use ($userId, $date) {
            return [
                'user_id' => $userId,
                'work_date' => $date,
            ];
        });
    }
}
