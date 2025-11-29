<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. 勤怠データのシーダー
        $this->call([
            AttendanceSeeder::class,
        ]);

        // 2. 管理者の作成
        Admin::updateOrCreate(
            ['email' => 'admin@mail.com'],
            ['password' => bcrypt('00000000')]
        );
    }
}
