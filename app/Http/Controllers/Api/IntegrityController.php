<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\CheatLog;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IntegrityController extends Controller
{
    /**
     * Maximum violations before auto-terminate (auto-submit).
     * Every fullscreen exit, tab switch, etc. counts as 1 violation.
     */
    const MAX_VIOLATIONS = 5;

    public function logViolation(Request $request)
    {
        // 1. Validasi data dari Frontend
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'type'       => 'required|string|in:tab_switch,split_screen,window_blur,device_offline,screenshot,fullscreen_exit,resize_suspicion',
            'duration'   => 'required|integer|min:0'
        ]);

        return DB::transaction(function () use ($request) {
            $session = ExamSession::where('id', $request->session_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Authorization: only the session owner can log violations
            if ($session->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Cek status sesi
            if ($session->status !== 'ongoing') {
                return response()->json(['message' => 'Session closed'], 400);
            }

            // 2. Increment violation counter (race-safe under row lock)
            $newViolationCount = (int) $session->violation_count + 1;

            // 3. Also update legacy integrity score for backward compatibility
            $penaltyMap = [
                'tab_switch'       => 25,
                'fullscreen_exit'  => 20,
                'split_screen'     => 20,
                'resize_suspicion' => 15,
                'window_blur'      => 15,
                'screenshot'       => 30,
                'device_offline'   => 10,
            ];
            $penalty = $penaltyMap[$request->type] ?? 20;
            $newScore = max(0, (float) $session->score_integrity - $penalty);

            $session->update([
                'score_integrity' => $newScore,
                'violation_count' => $newViolationCount,
            ]);

            // 4. Simpan Log Detail (Bukti Forensik)
            CheatLog::create([
                'exam_session_id' => $session->id,
                'violation_type'  => $request->type,
                'duration_seconds' => $request->duration,
                'occurred_at'     => now(),
            ]);

            // 5. AUTO-SUBMIT server-side if threshold reached.
            $terminated = false;
            $autoSubmit = false;
            if ($newViolationCount >= self::MAX_VIOLATIONS) {
                $autoSubmit = true;
                $terminated = true;

                // Finalize session immediately so status stays consistent
                // even if client disconnects during auto-submit flow.
                $questions = $session->exam()->with('questions:id,exam_id,question_type,correct_answer,points')->first()?->questions?->keyBy('id') ?? collect();
                $totalPoints = $questions->sum('points') ?: $questions->count();

                $earnedPoints = 0;
                $answers = StudentAnswer::where('exam_session_id', $session->id)
                    ->get(['id', 'question_id', 'selected_answer', 'is_correct']);

                foreach ($answers as $answer) {
                    $question = $questions->get($answer->question_id);
                    if (!$question) {
                        continue;
                    }

                    $isCorrectNow = false;
                    if (($question->question_type ?? 'multiple_choice') !== 'essay' && $answer->selected_answer) {
                        $selected = strtolower(trim((string) $answer->selected_answer));
                        $correct = strtolower(trim((string) $question->correct_answer));
                        $isCorrectNow = $selected !== '' && $selected === $correct;
                    }

                    if ($answer->is_correct !== $isCorrectNow) {
                        StudentAnswer::where('id', $answer->id)->update([
                            'is_correct' => $isCorrectNow,
                        ]);
                    }

                    if ($isCorrectNow) {
                        $earnedPoints += ($question->points ?: 1);
                    }
                }

                $academicScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;
                $session->update([
                    'score_academic' => $academicScore,
                    'status' => 'completed',
                    'end_time' => now(),
                ]);
            }

            // Clear ALL poll caches so admin sees updated integrity immediately
            Cache::forget("exam.{$session->exam_id}.lobby_status");
            Cache::forget("exam.{$session->exam_id}.poll_status");

            return response()->json([
                'status' => 'logged',
                'penalty_applied' => $penalty,
                'current_integrity' => $newScore,
                'violation_count' => $newViolationCount,
                'max_violations' => self::MAX_VIOLATIONS,
                'remaining_violations' => max(0, self::MAX_VIOLATIONS - $newViolationCount),
                'terminated' => $terminated,
                'auto_submit' => $autoSubmit,
            ]);
        });
    }

    public function getStatus($session_id)
    {
        $session = ExamSession::select('id', 'user_id', 'exam_id', 'score_integrity', 'violation_count', 'status')
            ->findOrFail($session_id);

        // Authorization: only the session owner can view status
        if ($session->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'score_integrity' => $session->score_integrity,
            'violation_count' => $session->violation_count,
            'max_violations' => self::MAX_VIOLATIONS,
            'remaining_violations' => max(0, self::MAX_VIOLATIONS - $session->violation_count),
            'status' => $session->status,
        ]);
    }
}