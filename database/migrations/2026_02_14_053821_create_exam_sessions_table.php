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
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('exam_id')->constrained();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            
            // Core Anti-Cheat
            $table->float('score_academic')->default(0); // Nilai Jawaban
            $table->float('score_integrity')->default(100); // Nilai Kejujuran (Start 100%)
            $table->enum('status', ['ongoing', 'completed', 'blocked'])->default('ongoing');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
