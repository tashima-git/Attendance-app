<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceCorrectionRequest;
use App\Models\CorrectionBreakTime;
use Carbon\Carbon;

class AttendanceCorrectionRequestFactory extends Factory
{
    protected $model = AttendanceCorrectionRequest::class;

    public function definition(): array
    {
        // 出勤・退勤時間をランダム生成（8:00～10:00 / 17:00～19:00）
        $clockInHour = rand(8, 10);
        $clockInMinute = rand(0, 59);
        $clockOutHour = rand(17, 19);
        $clockOutMinute = rand(0, 59);

        $clockIn = Carbon::createFromTime($clockInHour, $clockInMinute)->format('H:i:s');
        $clockOut = Carbon::createFromTime($clockOutHour, $clockOutMinute)->format('H:i:s');

        return [
            'user_id'       => null, // Seederで設定
            'attendance_id' => null, // Seederで設定
            'clock_in'      => $clockIn,
            'clock_out'     => $clockOut,
            'remarks'       => $this->faker->sentence(6),
            'status'        => 'pending',
            'approved_by'   => null,
            'approved_at'   => null,
        ];
    }

    /**
     * 指定した勤怠IDに紐づけ
     */
    public function forAttendance(int $attendanceId): static
    {
        return $this->state(fn() => ['attendance_id' => $attendanceId]);
    }

    /**
     * 作成後に休憩時間を紐づける
     */
    public function configure(): static
    {
        return $this->afterCreating(function (AttendanceCorrectionRequest $request) {
            // 休憩回数は1～3回ランダム
            $numBreaks = rand(1, 3);
            $clockIn = Carbon::createFromFormat('H:i:s', $request->clock_in);

            for ($i = 0; $i < $numBreaks; $i++) {
                $breakStart = $clockIn->copy()->addHours(rand(1, 5))->addMinutes(rand(0, 30));
                $breakEnd   = $breakStart->copy()->addMinutes(rand(30, 60));

                CorrectionBreakTime::create([
                    'correction_request_id' => $request->id,
                    'break_start'           => $breakStart->format('H:i:s'),
                    'break_end'             => $breakEnd->format('H:i:s'),
                    'total_break_time'      => $breakStart->diffInMinutes($breakEnd),
                ]);
            }
        });
    }
}
