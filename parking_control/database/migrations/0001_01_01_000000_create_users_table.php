<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::create('users', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('email')->unique();
        //     $table->timestamp('email_verified_at')->nullable();
        //     $table->string('password');
        //     $table->rememberToken();
        //     $table->timestamps();
        // });

        // Schema::create('password_reset_tokens', function (Blueprint $table) {
        //     $table->string('email')->primary();
        //     $table->string('token');
        //     $table->timestamp('created_at')->nullable();
        // });

        // Schema::create('sessions', function (Blueprint $table) {
        //     $table->string('id')->primary();
        //     $table->foreignId('user_id')->nullable()->index();
        //     $table->string('ip_address', 45)->nullable();
        //     $table->text('user_agent')->nullable();
        //     $table->longText('payload');
        //     $table->integer('last_activity')->index();
        // });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Bảng mã khôi phục mật khẩu - Đã giới hạn độ dài Email làm khóa chính an toàn
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 255)->primary(); // Giới hạn 255 ký tự để làm khóa chính trên Postgres
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Bảng quản lý phiên đăng nhập (Sessions) - Đã tối ưu khóa chính và trường thời gian
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary(); // Giới hạn 255 ký tự để làm khóa chính an toàn
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->bigInteger('last_activity')->index(); // Đổi sang bigInteger để tối ưu index thời gian trên Postgres
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
