<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // 主キー
            $table->string('name', 20)->nullable(false); // ユーザー名（必須）
            $table->string('email', 255)->unique()->nullable(false); // メールアドレス（必須・一意）
            $table->timestamp('email_verified_at')->nullable(); // メール認証日時（nullable）
            $table->string('password', 255)->nullable(false); // パスワード
            $table->rememberToken(); // ログイン状態を保持するトークン
            $table->timestamps(); // created_at / updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
