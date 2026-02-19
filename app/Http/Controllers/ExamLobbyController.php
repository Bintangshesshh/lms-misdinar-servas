<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamLobbyController extends Controller
{
    public function joinLobby(Exam $exam)
    {
        if (!$exam->isLobbyOpen()) {
            return back()->with('error', 'Lobby belum dibuka oleh Admin.');
        }

        // Check if already joined
        $session = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->first();

        if (!$session) {
            $session = ExamSession::create([
                'user_id' => Auth::id(),
                'exam_id' => $exam->id,
                'start_time' => now(),
                'joined_at' => now(),
                'score_integrity' => 100,
                'status' => 'ongoing',
            ]);
        }

        return redirect()->route('student.exam.lobby', $exam);
    }

    public function studentLobby(Exam $exam)
    {
        $session = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->first();

        if (!$session) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Anda belum join lobby.');
        }

        return view('student.exam-lobby', compact('exam', 'session'));
    }

    public function takeExam(Exam $exam)
    {
        $session = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        // Only allow if exam has started
        if (!$exam->isStarted() && $exam->status !== 'started') {
            return redirect()->route('student.exam.lobby', $exam);
        }

        // Block terminated students
        if ($session->status === 'blocked') {
            return redirect()->route('student.dashboard')
                ->with('error', 'Anda telah di-terminate dari ujian ini.');
        }

        // Load questions with eager loading (cache for 10 minutes since questions don't change)
        $questions = \Illuminate\Support\Facades\Cache::remember(
            "exam.{$exam->id}.questions",
            600,
            fn() => $exam->questions()->orderBy('order')->get()
        );

        // Load existing answers for this session in single query
        $answers = StudentAnswer::where('exam_session_id', $session->id)
            ->pluck('selected_answer', 'question_id')
            ->toArray();

        return view('student.exam-take', compact('exam', 'session', 'questions', 'answers'));
    }

    /**
     * Save a single answer (auto-save via AJAX)
     */
    public function saveAnswer(Request $request, Exam $exam)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_answer' => 'required|in:a,b,c,d',
        ]);

        $session = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->where('status', 'ongoing')
            ->firstOrFail();

        $question = $exam->questions()->findOrFail($request->question_id);

        $isCorrect = $question->correct_answer === $request->selected_answer;

        StudentAnswer::updateOrCreate(
            [
                'exam_session_id' => $session->id,
                'question_id' => $question->id,
            ],
            [
                'selected_answer' => $request->selected_answer,
                'is_correct' => $isCorrect,
            ]
        );

        // Count answered questions
        $totalQuestions = $exam->questions()->count();
        $answeredCount = StudentAnswer::where('exam_session_id', $session->id)
            ->whereNotNull('selected_answer')
            ->count();

        return response()->json([
            'success' => true,
            'answered' => $answeredCount,
            'total' => $totalQuestions,
        ]);
    }

    /**
     * Submit the exam (finalize)
     */
    public function submitExam(Request $request, Exam $exam)
    {
        $session = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->where('status', 'ongoing')
            ->firstOrFail();

        // Eager load questions to avoid N+1 query
        $questions = \Illuminate\Support\Facades\Cache::remember(
            "exam.{$exam->id}.questions",
            600,
            fn() => $exam->questions()->get()->keyBy('id')
        );
        
        $totalPoints = $questions->sum('points');
        $earnedPoints = 0;

        // Load answers with question relationship in single query
        $answers = StudentAnswer::where('exam_session_id', $session->id)
            ->where('is_correct', true)
            ->get();
            
        foreach ($answers as $answer) {
            $question = $questions->get($answer->question_id);
            if ($question) {
                $earnedPoints += $question->points;
            }
        }

        $academicScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;

        // Update session
        $session->update([
            'score_academic' => $academicScore,
            'status' => 'completed',
            'end_time' => now(),
        ]);

        return redirect()->route('student.exam.result', $exam)
            ->with('success', 'Ujian telah diselesaikan!');
    }

    /**
     * Show exam result
     */
    public function examResult(Exam $exam)
    {
        $session = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        // Cache questions (they don't change after exam creation)
        $questions = \Illuminate\Support\Facades\Cache::remember(
            "exam.{$exam->id}.questions",
            600,
            fn() => $exam->questions()->orderBy('order')->get()
        );
        
        // Load answers in single optimized query
        $answers = StudentAnswer::where('exam_session_id', $session->id)
            ->pluck('selected_answer', 'question_id')
            ->toArray();

        $correctAnswers = StudentAnswer::where('exam_session_id', $session->id)
            ->where('is_correct', true)
            ->count();

        return view('student.exam-result', compact('exam', 'session', 'questions', 'answers', 'correctAnswers'));
    }

    // Polling endpoint for students to check exam status
    public function pollStatus(Exam $exam)
    {
        $exam->refresh();

        // Also check if the current student's session was terminated by admin
        $sessionStatus = null;
        $sessionIntegrity = null;
        $user = Auth::user();
        if ($user) {
            $session = ExamSession::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->first();
            if ($session) {
                $sessionStatus = $session->status;
                $sessionIntegrity = $session->score_integrity;
            }
        }

        return response()->json([
            'exam_status' => $exam->status,
            'started_at' => $exam->started_at?->toISOString(),
            'session_status' => $sessionStatus,
            'session_integrity' => $sessionIntegrity,
        ]);
    }
}
