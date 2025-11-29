<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('correction_break_times', function (Blueprint $table) {
            $table->id();

            // 修正申請に紐づく
            $table->foreignId('correction_request_id')
                ->constrained('attendance_correction_requests')
                ->onDelete('cascade');

            $table->time('break_start')->nullable(false);
            $table->time('break_end')->nullable(false);

            // 分単位の休憩時間
            $table->integer('total_break_time')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_break_times');
    }
};
