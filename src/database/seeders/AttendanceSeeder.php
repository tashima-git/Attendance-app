<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\CorrectionBreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // --- ① 通常ユーザー ---
        $names = [
            '山田 太郎', '西 伶奈', '増田 一世', '山本 敬吉', '秋田 朋美', '中西 教夫'
        ];

        $users = collect($names)->map(function ($name) {
            return User::factory()->create([
                'name'  => $name,
                'email' => uniqid() . '@example.com',
            ]);
        });

        // --- ② テストユーザー（重複チェック） ---
        $testUser = User::firstOrCreate(
            ['email' => 'test@mail.com'],
            [
                'name'     => 'テスト 太郎',
                'password' => Hash::make('00000000'),
            ]
        );

        // --- ③ 日付範囲：先月1日〜来月末（約90日） ---
        $startDate = Carbon::today()->subMonth()->firstOfMonth();
        $endDate   = Carbon::today()->addMonth()->endOfMonth();
        $days      = $startDate->diffInDays($endDate) + 1;

        // 全ユーザー（テストユーザー含む）
        $allUsers = $users->push($testUser);

        foreach ($allUsers as $user) {
            for ($i = 0; $i < $days; $i++) {

                $date = $startDate->copy()->addDays($i)->format('Y-m-d');
                $carbonDate = Carbon::parse($date);

                // --- 土日は休みとしてスキップ ---
                if ($carbonDate->isWeekend()) {
                    continue;
                }

                // --- 勤怠本体 ---
                $attendance = Attendance::factory()
                    ->forUserAndDate($user->id, $date)
                    ->create();

                // --- 休憩時間（1〜3回ランダム、重複なし） ---
                $numBreaks = rand(1, 3);
                $clockIn   = Carbon::createFromFormat('H:i:s', $attendance->clock_in);
                $clockOut  = Carbon::createFromFormat('H:i:s', $attendance->clock_out);

                $generatedBreaks = [];

                for ($j = 0; $j < $numBreaks; $j++) {

                    while (true) {
                        // 休憩開始（出勤後〜退勤2時間前まで）
                        $breakStart = $clockIn->copy()
                            ->addHours(rand(1, 5))
                            ->addMinutes(rand(0, 30));

                        // 休憩終了
                        $breakEnd = $breakStart->copy()->addMinutes(rand(15, 60));

                        // 退勤時間を超えない
                        if ($breakEnd->greaterThanOrEqualTo($clockOut)) {
                            continue;
                        }

                        // 既存の休憩と重複しないかチェック
                        $overlap = false;
                        foreach ($generatedBreaks as $b) {
                            if (
                                $breakStart->between($b['start'], $b['end']) ||
                                $breakEnd->between($b['start'], $b['end'])
                            ) {
                                $overlap = true;
                                break;
                            }
                        }

                        if (!$overlap) {
                            // OK → 配列へ確定
                            $generatedBreaks[] = [
                                'start' => $breakStart,
                                'end'   => $breakEnd
                            ];
                            break;
                        }
                    }

                    // --- DB へ保存 ---
                    $attendance->breakTimes()->create([
                        'break_start'      => $breakStart->format('H:i:s'),
                        'break_end'        => $breakEnd->format('H:i:s'),
                        'total_break_time' => $breakStart->diffInMinutes($breakEnd),
                    ]);
                }

                // --- 修正申請（20%） ---
                if ($user->email !== 'test@mail.com' && rand(1, 100) <= 10) {
                    AttendanceCorrectionRequest::factory()
                        ->forAttendance($attendance->id)
                        ->state(['user_id' => $user->id])
                        ->create();
                }
            }
        }
    }
}
