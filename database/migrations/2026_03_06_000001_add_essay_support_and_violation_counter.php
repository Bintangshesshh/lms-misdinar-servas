<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add question_type to questions table (for essay support)
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('question_type', ['multiple_choice', 'essay'])->default('multiple_choice')->after('exam_id');
            // Make option columns nullable for essay questions
            $table->string('option_a')->nullable()->change();
            $table->string('option_b')->nullable()->change();
            $table->string('option_c')->nullable()->change();
            $table->string('option_d')->nullable()->change();
        });

        // 2. Make correct_answer nullable (essay has no correct answer)
        DB::statement("ALTER TABLE questions MODIFY correct_answer ENUM('a','b','c','d') NULL DEFAULT NULL");

        // 3. Add essay_answer column to student_answers
        Schema::table('student_answers', function (Blueprint $table) {
            $table->text('answer_text')->nullable()->after('selected_answer');
        });

        // 3. Add violation_count to exam_sessions for the new cheat counter system
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->integer('violation_count')->default(0)->after('score_integrity');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
            $table->string('option_a')->nullable(false)->change();
            $table->string('option_b')->nullable(false)->change();
            $table->string('option_c')->nullable(false)->change();
            $table->string('option_d')->nullable(false)->change();
        });

        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropColumn('answer_text');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('violation_count');
        });
    }
};
