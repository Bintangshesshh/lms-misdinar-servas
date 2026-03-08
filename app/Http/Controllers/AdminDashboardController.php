<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\StudentAnswer;
use App\Exports\ExamResultExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    private function heartbeatCacheKey(int $examId, int $sessionId): string
    {
        return "exam.{$examId}.session.{$sessionId}.heartbeat";
    }

    private function shouldDisplayInMonitor(int $examId, ExamSession $session): bool
    {
        if ($session->status !== 'ongoing') {
            return true;
        }

        // Never hide active sessions that already have join/start timestamps.
        // Heartbeat is only a fallback signal for legacy/incomplete rows.
        if ($session->joined_at || $session->start_time) {
            return true;
        }

        return Cache::has($this->heartbeatCacheKey($examId, (int) $session->id));
    }

    public function index()
    {
        // Cache exam list for 30 seconds to reduce DB load
        $exams = Cache::remember('admin.exams.list', 30, function () {
            return Exam::withCount(['sessions', 'questions'])->get();
        });
        
        return view('admin.dashboard', compact('exams'));
    }

    public function monitor(Exam $exam)
    {
        $exam->load(['lobbyStudents']);
        return view('admin.exam-monitor', compact('exam'));
    }

    public function openLobby(Exam $exam)
    {
        $exam->update(['status' => 'lobby']);
        
        // Clear caches when exam status changes
        Cache::forget('admin.exams.list');
        Cache::forget("exam.{$exam->id}.lobby_status");
        Cache::forget("exam.{$exam->id}.poll_status");
        
        return back()->with('success', 'Lobby dibuka! Siswa sekarang bisa join.');
    }

    public function startExam(Exam $exam)
    {
        $exam->refresh();

        if ($exam->status !== 'lobby') {
            return back()->with('error', 'Ujian hanya bisa dimulai saat status lobby.');
        }

        if (!$exam->questions()->exists()) {
            return back()->with('error', 'Ujian belum memiliki soal. Tambahkan soal terlebih dahulu.');
        }

        // Atomic status transition to prevent duplicate concurrent starts.
        $updated = Exam::whereKey($exam->id)
            ->where('status', 'lobby')
            ->update([
                'status' => 'countdown',
                'started_at' => now()->addSeconds(5),
            ]);

        if (!$updated) {
            return back()->with('error', 'Ujian sedang diproses atau sudah dimulai. Silakan refresh monitor.');
        }

        // No blocking dispatch needed - isStarted() handles lazy transition
        // When anyone checks exam status after 5 seconds, it auto-updates to 'started'

        // Clear caches for countdown
        Cache::forget('admin.exams.list');
        Cache::forget("exam.{$exam->id}.lobby_status");
        Cache::forget("exam.{$exam->id}.poll_status");

        return back()->with('success', 'Countdown dimulai! Ujian akan dimulai dalam 5 detik.');
    }

    /**
     * JSON endpoint for admin polling — optimized for 100+ concurrent students.
     * Shows REAL-TIME progress and estimated score.
     */
    public function lobbyStatus(Exam $exam)
    {
        // Always get fresh data from DB (no stale model binding)
        $exam->refresh();

        // Trigger lazy countdown → started transition (updates both DB + in-memory)
        $exam->isStarted();

        // Clear poll cache so students also see the transition immediately
        if ($exam->wasChanged('status')) {
            Cache::forget("exam.{$exam->id}.poll_status");
        }

        // Cache total questions (doesn't change during exam)
        $totalQuestions = Cache::remember("exam.{$exam->id}.total_questions", 600, function () use ($exam) {
            return $exam->questions()->count();
        });

        // Cache total points for score calculation
        $totalPoints = Cache::remember("exam.{$exam->id}.total_points", 600, function () use ($exam) {
            return $exam->questions()->sum('points') ?: $exam->questions()->count();
        });

        // Aggregate earned points by session with a lightweight grouped query.
        // Short cache reduces DB pressure while keeping monitor near real-time.
        $earnedPointsBySession = Cache::remember("exam.{$exam->id}.earned_points", 2, function () use ($exam) {
            return StudentAnswer::query()
                ->join('questions', 'student_answers.question_id', '=', 'questions.id')
                ->join('exam_sessions', 'student_answers.exam_session_id', '=', 'exam_sessions.id')
                ->where('exam_sessions.exam_id', $exam->id)
                ->where('student_answers.is_correct', true)
                ->groupBy('student_answers.exam_session_id')
                ->selectRaw('student_answers.exam_session_id as session_id, SUM(COALESCE(questions.points, 1)) as earned_points')
                ->pluck('earned_points', 'session_id');
        });

        $students = $exam->sessions()
            ->select('id', 'exam_id', 'user_id', 'start_time', 'joined_at', 'score_integrity', 'score_academic', 'status', 'violation_count', 'created_at')
            ->with([
                'user:id,name,email',
            ])
            ->withCount([
                'answers as answered_count' => function ($q) {
                    $q->where(function ($inner) {
                        $inner->whereNotNull('selected_answer')
                            ->orWhereNotNull('answer_text');
                    });
                },
                'answers as correct_count' => function ($q) {
                    $q->where('is_correct', true);
                },
            ])
            ->get()
            ->filter(function ($session) use ($exam) {
                return $this->shouldDisplayInMonitor((int) $exam->id, $session);
            })
            ->map(function($session) use ($exam, $totalPoints, $earnedPointsBySession) {
                $answeredCount = (int) ($session->answered_count ?? 0);
                $correctCount = (int) ($session->correct_count ?? 0);
                
                // Always derive score from current answers to avoid stale/null score mismatch.
                $earnedPoints = (float) ($earnedPointsBySession[$session->id] ?? 0);
                $currentScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;

                $timeRemaining = null;
                if ($exam->status === 'started' && $exam->started_at && $session->status === 'ongoing') {
                    // copy() avoids mutating $exam->started_at across loop iterations.
                    $endTime = $exam->started_at->copy()->addMinutes($exam->duration_minutes);
                    $remaining = now()->diffInSeconds($endTime, false);
                    $timeRemaining = (int) max(0, $remaining);
                }

                $joinedAt = $session->joined_at ?? $session->start_time ?? $session->created_at;

                return [
                    'id' => $session->user->id,
                    'session_id' => $session->id,
                    'name' => $session->user->name,
                    'email' => $session->user->email,
                    'joined_at' => $joinedAt ? $joinedAt->diffForHumans() : '-',
                    'integrity' => $session->score_integrity,
                    'violation_count' => $session->violation_count ?? 0,
                    'status' => $session->status,
                    'current_score' => $currentScore,
                    'answered' => $answeredCount,
                    'correct' => $correctCount,
                    'time_remaining' => $timeRemaining,
                ];
            });

        $data = [
            'exam_status' => $exam->status,
            'student_count' => $students->count(),
            'students' => $students,
            'total_questions' => $totalQuestions,
            'exam_duration' => $exam->duration_minutes,
        ];

        return response()->json($data);
    }

    /**
     * Terminate a student's exam session (admin action).
     */
    public function terminateStudent(Request $request, Exam $exam)
    {
        $request->validate(['session_id' => 'required|integer']);

        $session = ExamSession::where('id', $request->session_id)
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        $session->update(['status' => 'blocked']);

        Cache::forget($this->heartbeatCacheKey((int) $exam->id, (int) $session->id));

        // Clear caches so admin monitor and student poll see updated status immediately
        Cache::forget("exam.{$exam->id}.lobby_status");
        Cache::forget("exam.{$exam->id}.poll_status");

        return response()->json(['success' => true, 'message' => 'Siswa telah di-terminate.']);
    }

    /**
     * Reinstate a terminated student (admin action).
     */
    public function reinstateStudent(Request $request, Exam $exam)
    {
        $request->validate(['session_id' => 'required|integer']);

        $session = ExamSession::where('id', $request->session_id)
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        $session->update([
            'status' => 'ongoing',
            'score_integrity' => 60, // Reinstate dengan 60 poin, bukan 100
        ]);

        // Clear caches
        Cache::forget("exam.{$exam->id}.lobby_status");
        Cache::forget("exam.{$exam->id}.poll_status");

        return response()->json(['success' => true, 'message' => 'Siswa diizinkan melanjutkan dengan 60 poin integritas.']);
    }

    /**
     * Force stop the exam (admin emergency action).
     * All ongoing sessions are auto-submitted and scored.
     */
    /**
     * Stop an exam and finalize all ongoing sessions.
     * Optimized: loads questions once, bulk-loads answers, uses transactions.
     *
     * @param Request $request
     * @param Exam    $exam
     */
    public function stopExam(Request $request, Exam $exam)
    {
        $stopTime = now();

        // Freeze exam + sessions first so no further saveAnswer can mutate ongoing attempts.
        $ongoingSessionIds = \Illuminate\Support\Facades\DB::transaction(function () use ($exam, $stopTime) {
            $exam->update(['status' => 'finished']);

            $sessionIds = ExamSession::where('exam_id', $exam->id)
                ->where('status', 'ongoing')
                ->pluck('id');

            if ($sessionIds->isNotEmpty()) {
                ExamSession::whereIn('id', $sessionIds)
                    ->update([
                        'status' => 'completed',
                        'end_time' => $stopTime,
                    ]);
            }

            return $sessionIds;
        });

        if ($ongoingSessionIds->isNotEmpty()) {
            // Load questions once (keyed for fast lookups)
            $questions = $exam->questions()->get()->keyBy('id');
            $totalPoints = $questions->sum('points') ?: $questions->count();

            foreach ($ongoingSessionIds->chunk(50) as $chunkIds) {
                $answersBySession = StudentAnswer::whereIn('exam_session_id', $chunkIds)
                    ->get(['id', 'exam_session_id', 'question_id', 'selected_answer', 'is_correct'])
                    ->groupBy('exam_session_id');

                foreach ($chunkIds as $sessionId) {
                    $sessionAnswers = $answersBySession->get($sessionId, collect());
                    $earnedPoints = 0;

                    foreach ($sessionAnswers as $answer) {
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

                    ExamSession::where('id', $sessionId)->update([
                        'score_academic' => $academicScore,
                    ]);
                }
            }
        }

        // Clear caches
        Cache::forget("exam.{$exam->id}.lobby_status");
        Cache::forget("exam.{$exam->id}.poll_status");
        Cache::forget("exam.{$exam->id}.earned_points");
        Cache::forget('admin.exams.list');

        return back()->with('success', 'Ujian dihentikan! Semua jawaban siswa telah disimpan dan dinilai.');
    }

    // ============================================
    // EXAM CRUD
    // ============================================

    public function createExam()
    {
        return view('admin.exam-create');
    }

    public function storeExam(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'mata_pelajaran' => 'nullable|string|max:100',
            'duration_minutes' => 'required|integer|min:1|max:600',
        ]);

        $exam = Exam::create([
            'title' => $request->title,
            'mata_pelajaran' => $request->mata_pelajaran,
            'duration_minutes' => $request->duration_minutes,
            'is_active' => true,
            'status' => 'draft',
            'show_answers' => $request->has('show_answers'),
            'show_score_to_student' => $request->has('show_score_to_student'),
        ]);

        // Redirect to kelola soal so admin can immediately add questions
        return redirect()->route('admin.questions.show', $exam)->with('success', 'Ujian "' . $request->title . '" berhasil dibuat! Silakan tambahkan soal.');
    }

    public function editExam(Exam $exam)
    {
        return view('admin.exam-edit', compact('exam'));
    }

    public function updateExam(Request $request, Exam $exam)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'mata_pelajaran' => 'nullable|string|max:100',
            'duration_minutes' => 'required|integer|min:1|max:600',
        ]);

        $exam->update([
            'title' => $request->title,
            'mata_pelajaran' => $request->mata_pelajaran,
            'duration_minutes' => $request->duration_minutes,
            'show_answers' => $request->has('show_answers'),
            'show_score_to_student' => $request->has('show_score_to_student'),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Ujian "' . $exam->title . '" berhasil diperbarui!');
    }

    public function deleteExam(Exam $exam)
    {
        // Only allow deleting draft or finished exams
        if (in_array($exam->status, ['started', 'countdown', 'lobby'])) {
            return back()->with('error', 'Tidak bisa menghapus ujian yang sedang berlangsung!');
        }

        $title = $exam->title;
        $exam->questions()->delete();
        $exam->sessions()->delete();
        $exam->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Ujian "' . $title . '" berhasil dihapus.');
    }

    /**
     * Reset exam back to draft so it can be reused.
     */
    /**
     * Reset exam back to draft so it can be reused.
     * Optimized: bulk delete using whereIn instead of N+1 loops.
     */
    public function resetExam(Exam $exam)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($exam) {
            $sessionIds = $exam->sessions()->pluck('id');
            
            // Bulk delete all related data
            \App\Models\StudentAnswer::whereIn('exam_session_id', $sessionIds)->delete();
            \App\Models\CheatLog::whereIn('exam_session_id', $sessionIds)->delete();
            $exam->sessions()->delete();

            $exam->update([
                'status' => 'draft',
                'started_at' => null,
            ]);
        });

        // Clear caches
        Cache::forget("exam.{$exam->id}.lobby_status");
        Cache::forget("exam.{$exam->id}.poll_status");
        Cache::forget("exam.{$exam->id}.questions");
        Cache::forget("exam.{$exam->id}.questions.ordered");
        Cache::forget("exam.{$exam->id}.questions.by_id");
        Cache::forget("exam.{$exam->id}.total_questions");
        Cache::forget('admin.exams.list');

        return back()->with('success', 'Ujian "' . $exam->title . '" telah di-reset ke Draft.');
    }

    /**
     * Export exam results to Excel (.xlsx)
     */
    public function exportResult(Exam $exam)
    {
        $export = new ExamResultExport($exam);
        $filePath = $export->export();
        $filename = basename($filePath);

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
