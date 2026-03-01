<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Exports\ExamResultExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
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
        $exam->update([
            'status' => 'countdown',
            'started_at' => now()->addSeconds(5),
        ]);

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
        // Trigger lazy countdown → started transition
        $exam->isStarted();

        // Cache total questions (doesn't change during exam)
        $totalQuestions = Cache::remember("exam.{$exam->id}.total_questions", 600, function () use ($exam) {
            return $exam->questions()->count();
        });

        // Cache total points for score calculation
        $totalPoints = Cache::remember("exam.{$exam->id}.total_points", 600, function () use ($exam) {
            return $exam->questions()->sum('points') ?: $exam->questions()->count();
        });

        $students = $exam->sessions()
            ->whereNotNull('joined_at')
            ->select('id', 'exam_id', 'user_id', 'joined_at', 'score_integrity', 'score_academic', 'status')
            ->with([
                'user:id,name,email',
                'answers:id,exam_session_id,is_correct,question_id'
            ])
            ->get()
            ->map(function($session) use ($exam, $totalQuestions, $totalPoints) {
                $answeredCount = $session->answers->count();
                $correctCount = $session->answers->filter(fn($a) => $a->is_correct)->count();
                
                // Real-time score estimate (during exam) or final score (after completion)
                if ($session->status === 'completed' && $session->score_academic !== null) {
                    $currentScore = $session->score_academic;
                } else {
                    // Calculate real-time score estimate
                    $currentScore = $totalPoints > 0 ? round(($correctCount / $totalQuestions) * 100, 1) : 0;
                }

                $timeRemaining = null;
                if ($exam->status === 'started' && $exam->started_at && $session->status === 'ongoing') {
                    $endTime = $exam->started_at->addMinutes($exam->duration_minutes);
                    $remaining = now()->diffInSeconds($endTime, false);
                    $timeRemaining = (int) max(0, $remaining);
                }

                return [
                    'id' => $session->user->id,
                    'session_id' => $session->id,
                    'name' => $session->user->name,
                    'email' => $session->user->email,
                    'joined_at' => $session->joined_at->diffForHumans(),
                    'integrity' => $session->score_integrity,
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
        \Illuminate\Support\Facades\DB::transaction(function () use ($exam) {
            // Update exam status to finished
            $exam->update(['status' => 'finished']);

            // Load questions once
            $questions = $exam->questions;
            $totalPoints = $questions->sum('points');

            // Get all ongoing sessions with their answers in ONE query
            $ongoingSessions = ExamSession::where('exam_id', $exam->id)
                ->where('status', 'ongoing')
                ->with('answers')
                ->get();

            /** @var ExamSession $session */
            foreach ($ongoingSessions as $session) {
                $earnedPoints = 0;
                foreach ($session->answers as $answer) {
                    if ($answer->is_correct) {
                        $question = $questions->firstWhere('id', $answer->question_id);
                        if ($question) $earnedPoints += $question->points;
                    }
                }
                $academicScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;

                $session->update([
                    'score_academic' => $academicScore,
                    'status' => 'completed',
                    'end_time' => now(),
                ]);
            }
        });

        // Clear caches
        Cache::forget("exam.{$exam->id}.lobby_status");
        Cache::forget("exam.{$exam->id}.poll_status");
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
