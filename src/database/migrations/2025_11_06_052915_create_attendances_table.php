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
            $table->unsignedBigInteger('user_id');

            // 勤務日
            $table->date('work_date')->nullable(false);

            // 出勤・退勤
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();

            // 勤務時間（分）
            $table->integer('total_work_time')->nullable();

            // 備考（任意）
            $table->text('remarks')->nullable();

            $table->timestamps();

            // 外部キー制約（SQLiteでも使える）
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
