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
        // Schema::create('cache', function (Blueprint $table) {
        //     $table->string('key')->primary();
        //     $table->mediumText('value');
        //     $table->integer('expiration');
        // });

        // Schema::create('cache_locks', function (Blueprint $table) {
        //     $table->string('key')->primary();
        //     $table->string('owner');
        //     $table->integer('expiration');
        // });
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key', 255)->primary(); // Giới hạn 255 ký tự để làm khóa chính an toàn trên Postgres
            $table->mediumText('value');
            $table->bigInteger('expiration');      // Đổi sang bigInteger để đồng bộ dữ liệu thời gian
        });

        // 2. Bảng quản lý khóa Cache (Lock) - Tránh xung đột tiến trình
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 255)->primary(); // Giới hạn 255 ký tự làm khóa chính
            $table->string('owner');
            $table->bigInteger('expiration');      // Đổi sang bigInteger để chạy mượt trên Postgres
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
