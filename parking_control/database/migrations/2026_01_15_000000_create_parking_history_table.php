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
        Schema::create('parking_history', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number');
            $table->string('car_owner')->nullable();
            $table->enum('action', ['Entry', 'Exit'])->comment('Entry: Xe vào, Exit: Xe ra');
            $table->timestamp('action_time')->useCurrent();
            $table->string('notes')->nullable();
            $table->timestamps();
            
            $table->index('plate_number');
            $table->index('action_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_history');
    }
};
