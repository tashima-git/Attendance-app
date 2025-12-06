<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // 紐づくユーザー
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // 勤務日
            $table->date('work_date')->nullable(false);

            // 出勤・退勤
            $table->time('clock_in')->nullable(false);
            $table->time('clock_out')->nullable();

            // 勤務時間（分）
            $table->integer('total_work_time')->nullable();

            // 備考（任意）
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
