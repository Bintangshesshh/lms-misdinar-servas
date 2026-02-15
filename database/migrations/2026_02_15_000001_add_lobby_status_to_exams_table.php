<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->enum('status', ['draft', 'lobby', 'countdown', 'started', 'finished'])
                  ->default('draft')
                  ->after('is_active');
            $table->timestamp('started_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['status', 'started_at']);
        });
    }
};