<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('break_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->time('break_start')->nullable(false);
            $table->time('break_end')->nullable();
            $table->integer('total_break_time')->nullable();
            $table->timestamps();

            // 外部キー制約（SQLiteでも使える）
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('break_times');
    }
};
