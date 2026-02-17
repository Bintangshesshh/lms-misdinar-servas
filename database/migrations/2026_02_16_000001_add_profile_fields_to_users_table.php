<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('name');
            $table->string('kelas')->nullable()->after('full_name');
            $table->integer('umur')->nullable()->after('kelas');
            $table->string('lingkungan')->nullable()->after('umur');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'kelas', 'umur', 'lingkungan']);
        });
    }
};
