<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Exports\ExamResultExport;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $exams = Exam::withCount(['sessions', 'questions'])->get();
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
        return back()->with('success', 'Lobby dibuka! Siswa sekarang bisa join.');
    }

    public function startExam(Exam $exam)
    {
        $exam->update([
            'status' => 'countdown',
            'started_at' => now()->addSeconds(5),
        ]);

        dispatch(function () use ($exam) {
            sleep(5);
            $exam->update(['status' => 'started']);
        })->afterResponse();

        return back()->with('success', 'Countdown dimulai! Ujian akan dimulai dalam 5 detik.');
    }

    /**
     * JSON endpoint for admin polling — includes session status for monitoring.
     */
    public function lobbyStatus(Exam $exam)
    {
        $students = $exam->sessions()
            ->whereNotNull('joined_at')
            ->with('user:id,name,email')
            ->get()
            ->map(fn($s) => [
                'id' => $s->user->id,
                'session_id' => $s->id,
                'name' => $s->user->name,
                'email' => $s->user->email,
                'joined_at' => $s->joined_at->diffForHumans(),
                'integrity' => $s->score_integrity,
                'status' => $s->status,
            ]);

        return response()->json([
            'exam_status' => $exam->status,
            'student_count' => $students->count(),
            'students' => $students,
        ]);
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

        return response()->json(['success' => true, 'message' => 'Siswa diizinkan melanjutkan dengan 60 poin integritas.']);
    }

    /**
     * Force stop the exam (admin emergency action).
     * All ongoing sessions are auto-submitted and scored.
     */
    /**
     * Stop an exam and finalize all ongoing sessions.
     *
     * @param Request $request
     * @param Exam    $exam
     */
    public function stopExam(Request $request, Exam $exam)
    {
        // Update exam status to finished
        $exam->update(['status' => 'finished']);

        // Auto-complete all ongoing sessions
        $ongoingSessions = ExamSession::where('exam_id', $exam->id)
            ->where('status', 'ongoing')
            ->get();

        $questions = $exam->questions;
        $totalPoints = $questions->sum('points');

        /** @var ExamSession $session */
        foreach ($ongoingSessions as $session) {
            $earnedPoints = 0;
            $answers = \App\Models\StudentAnswer::where('exam_session_id', $session->id)->get();
            foreach ($answers as $answer) {
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
    public function resetExam(Exam $exam)
    {
        // Delete all sessions and answers
        foreach ($exam->sessions as $session) {
            \App\Models\StudentAnswer::where('exam_session_id', $session->id)->delete();
            \App\Models\CheatLog::where('exam_session_id', $session->id)->delete();
        }
        $exam->sessions()->delete();

        $exam->update([
            'status' => 'draft',
            'started_at' => null,
        ]);

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
