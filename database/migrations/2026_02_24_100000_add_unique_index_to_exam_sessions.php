<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            // Prevent duplicate sessions for same user+exam at database level
            $table->unique(['user_id', 'exam_id'], 'exam_sessions_user_exam_unique');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropUnique('exam_sessions_user_exam_unique');
        });
    }
};
