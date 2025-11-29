<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();

            // 紐づく勤怠データ（nullableにして、勤怠が無くても申請可能）
            $table->foreignId('attendance_id')
                ->nullable()
                ->constrained('attendances')
                ->nullOnDelete(); // 勤怠削除時はnullに

            // 申請対象日（勤怠が無い日も指定可能）
            $table->date('work_date')->nullable();

            // 申請したユーザー
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // 申請後の出退勤（nullableにして、未入力でも申請可能）
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();

            // 備考
            $table->string('remarks', 255)->nullable();

            // ステータス
            $table->enum('status', ['pending', 'approved'])
                ->default('pending');

            // 承認した管理者
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('admins')
                ->nullOnDelete();

            // 承認日時
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
};
