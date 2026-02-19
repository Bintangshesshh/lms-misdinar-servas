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
        // Check and add indexes only if they don't exist
        
        // EXAMS TABLE
        if (!\Illuminate\Support\Facades\Schema::hasIndex('exams', 'idx_exams_status')) {
            \Illuminate\Support\Facades\Schema::table('exams', function (Blueprint $table) {
                $table->index('status', 'idx_exams_status');
            });
        }
        
        if (!\Illuminate\Support\Facades\Schema::hasIndex('exams', 'idx_exams_is_active')) {
            \Illuminate\Support\Facades\Schema::table('exams', function (Blueprint $table) {
                $table->index('is_active', 'idx_exams_is_active');
            });
        }
        
        if (!\Illuminate\Support\Facades\Schema::hasIndex('exams', 'idx_exams_started_at')) {
            \Illuminate\Support\Facades\Schema::table('exams', function (Blueprint $table) {
                $table->index('started_at', 'idx_exams_started_at');
            });
        }

        // EXAM_SESSIONS TABLE
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (!\Illuminate\Support\Facades\Schema::hasIndex('exam_sessions', 'idx_sessions_status')) {
                $table->index('status', 'idx_sessions_status');
            }
            if (!\Illuminate\Support\Facades\Schema::hasIndex('exam_sessions', 'idx_sessions_integrity')) {
                $table->index('score_integrity', 'idx_sessions_integrity');
            }
            if (!\Illuminate\Support\Facades\Schema::hasIndex('exam_sessions', 'idx_sessions_exam_user')) {
                $table->index(['exam_id', 'user_id'], 'idx_sessions_exam_user');
            }
            if (!\Illuminate\Support\Facades\Schema::hasIndex('exam_sessions', 'idx_sessions_joined_at')) {
                $table->index('joined_at', 'idx_sessions_joined_at');
            }
        });

        // QUESTIONS TABLE
        if (!\Illuminate\Support\Facades\Schema::hasIndex('questions', 'idx_questions_exam_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->index('exam_id', 'idx_questions_exam_id');
            });
        }

        // STUDENT_ANSWERS TABLE
        if (!\Illuminate\Support\Facades\Schema::hasIndex('student_answers', 'idx_answers_exam_session_id')) {
            Schema::table('student_answers', function (Blueprint $table) {
                $table->index('exam_session_id', 'idx_answers_exam_session_id');
            });
        }
        if (!\Illuminate\Support\Facades\Schema::hasIndex('student_answers', 'idx_answers_question_id')) {
            Schema::table('student_answers', function (Blueprint $table) {
                $table->index('question_id', 'idx_answers_question_id');
            });
        }

        // CHEAT_LOGS TABLE
        Schema::table('cheat_logs', function (Blueprint $table) {
            if (!\Illuminate\Support\Facades\Schema::hasIndex('cheat_logs', 'idx_cheat_logs_exam_session')) {
                $table->index('exam_session_id', 'idx_cheat_logs_exam_session');
            }
            if (!\Illuminate\Support\Facades\Schema::hasIndex('cheat_logs', 'idx_cheat_logs_violation_type')) {
                $table->index('violation_type', 'idx_cheat_logs_violation_type');
            }
            if (!\Illuminate\Support\Facades\Schema::hasIndex('cheat_logs', 'idx_cheat_logs_occurred')) {
                $table->index('occurred_at', 'idx_cheat_logs_occurred');
            }
        });

        // USERS TABLE
        if (!\Illuminate\Support\Facades\Schema::hasIndex('users', 'idx_users_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('email', 'idx_users_email');
            });
        }
        if (!\Illuminate\Support\Facades\Schema::hasIndex('users', 'idx_users_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role', 'idx_users_role');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('idx_exams_status');
            $table->dropIndex('idx_exams_is_active');
            $table->dropIndex('idx_exams_started_at');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_status');
            $table->dropIndex('idx_sessions_integrity');
            $table->dropIndex('idx_sessions_exam_user');
            $table->dropIndex('idx_sessions_joined_at');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_exam_id');
        });

        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropIndex('idx_answers_exam_session_id');
            $table->dropIndex('idx_answers_question_id');
        });

        Schema::table('cheat_logs', function (Blueprint $table) {
            $table->dropIndex('idx_cheat_logs_session');
            $table->dropIndex('idx_cheat_logs_type');
            $table->dropIndex('idx_cheat_logs_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_role');
        });
    }
};
