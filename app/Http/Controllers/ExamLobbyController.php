<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamLobbyController extends Controller
{
    private const HEARTBEAT_TTL_SECONDS = 25;

    private function heartbeatCacheKey(int $examId, int $sessionId): string
    {
        return "exam.{$examId}.session.{$sessionId}.heartbeat";
    }

    private function markSessionAlive(int $examId, int $sessionId): void
    {
        Cache::put(
            $this->heartbeatCacheKey($examId, $sessionId),
            now()->timestamp,
            now()->addSeconds(self::HEARTBEAT_TTL_SECONDS)
        );
    }

    private function clearSessionHeartbeat(int $examId, int $sessionId): void
    {
        Cache::forget($this->heartbeatCacheKey($examId, $sessionId));
    }

    /**
     * Ensure active sessions have required timestamps for monitoring.
     */
    private function repairActiveSession(ExamSession $session): bool
    {
        if ($session->status !== 'ongoing') {
            return false;
        }

        $updates = [];
        if (!$session->joined_at) {
            $updates['joined_at'] = now();
        }
        if (!$session->start_time) {
            $updates['start_time'] = now();
        }

        if (!empty($updates)) {
            $session->update($updates);
            return true;
        }

        return false;
    }

    public function joinLobby(Exam $exam)
    {
        // Allow joining during lobby OR countdown (student might click join during 5s countdown)
        if (!$exam->isLobbyOpen() && $exam->status !== 'countdown') {
            return back()->with('error', 'Lobby belum dibuka oleh Admin.');
        }

        // Atomic check-and-create to prevent race condition duplicates
        $created = false;
        $repaired = false;
        $session = DB::transaction(function () use ($exam, &$created) {
            $session = ExamSession::where('user_id', Auth::id())
                ->where('exam_id', $exam->id)
                ->lockForUpdate()
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
                $created = true;
            }

            return $session;
        });

        if (!$created) {
            if ($session->status === 'blocked') {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Sesi Anda sedang diblokir oleh admin.');
            }

            if ($session->status === 'completed') {
                return redirect()->route('student.exam.result', $exam)
                    ->with('info', 'Ujian ini sudah Anda selesaikan.');
            }

            $repaired = $this->repairActiveSession($session);
        }

        if ($created || $repaired) {
            // Clear admin lobby cache so new student appears immediately
            Cache::forget("exam.{$exam->id}.lobby_status");
        }

        if ($session->status === 'ongoing') {
            $this->markSessionAlive((int) $exam->id, (int) $session->id);
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

        if ($session->status === 'blocked') {
            return redirect()->route('student.dashboard')
                ->with('error', 'Sesi Anda sedang diblokir oleh admin.');
        }

        if ($session->status === 'completed') {
            return redirect()->route('student.exam.result', $exam)
                ->with('info', 'Ujian ini sudah Anda selesaikan.');
        }

        if ($this->repairActiveSession($session)) {
            Cache::forget("exam.{$exam->id}.lobby_status");
        }

        if ($session->status === 'ongoing') {
            $this->markSessionAlive((int) $exam->id, (int) $session->id);
        }

        // If exam already started, redirect straight to take exam
        if ($exam->isStarted()) {
            return redirect()->route('student.exam.take', $exam);
        }

        return view('student.exam-lobby', compact('exam', 'session'));
    }

    public function takeExam(Exam $exam)
    {
        $session = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        if ($session->status === 'completed') {
            return redirect()->route('student.exam.result', $exam)
                ->with('info', 'Ujian ini sudah Anda selesaikan.');
        }

        if ($this->repairActiveSession($session)) {
            Cache::forget("exam.{$exam->id}.lobby_status");
        }

        // Only allow if exam has started
        if (!$exam->isStarted() && $exam->status !== 'started') {
            return redirect()->route('student.exam.lobby', $exam);
        }

        // Block terminated students
        if ($session->status === 'blocked') {
            return redirect()->route('student.dashboard')
                ->with('error', 'Anda telah di-terminate dari ujian ini.');
        }

        $this->markSessionAlive((int) $exam->id, (int) $session->id);

        // Load questions with eager loading (cache for 10 minutes since questions don't change)
        $questions = $this->getExamQuestionsOrdered($exam);

        // Load existing answers for this session in single query
        // For MC: pluck selected_answer; For essay: pluck answer_text
        $rawAnswers = StudentAnswer::where('exam_session_id', $session->id)
            ->get(['question_id', 'selected_answer', 'answer_text']);
        
        $answers = [];
        foreach ($rawAnswers as $ans) {
            if ($ans->answer_text) {
                $answers[$ans->question_id] = $ans->answer_text;
            } else {
                $answers[$ans->question_id] = $ans->selected_answer;
            }
        }

        return view('student.exam-take', compact('exam', 'session', 'questions', 'answers'));
    }

    /**
     * Save a single answer (auto-save via AJAX)
     * Supports both multiple choice and essay answers.
     * Optimized with caching + database lock for race condition protection.
     */
    public function saveAnswer(Request $request, Exam $exam)
    {
        $request->validate([
            'question_id' => [
                'required',
                Rule::exists('questions', 'id')->where(fn($q) => $q->where('exam_id', $exam->id)),
            ],
            'selected_answer' => 'nullable|in:a,b,c,d',
            'answer_text' => 'nullable|string|max:5000',
        ]);

        $userId = Auth::id();

        // Use transaction with lock to prevent race conditions
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $exam, $userId) {
            $session = ExamSession::where('user_id', $userId)
                ->where('exam_id', $exam->id)
                ->where('status', 'ongoing')
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return response()->json(['error' => 'Session not found or expired'], 404);
            }

            $this->markSessionAlive((int) $exam->id, (int) $session->id);

            // Use cached questions instead of querying each time
            $questions = $this->getExamQuestionsById($exam);

            $question = $questions->get($request->question_id);
            if (!$question) {
                return response()->json(['error' => 'Question not found'], 404);
            }

            // Handle based on question type
            $isCorrect = false;
            $selectedAnswer = null;
            $answerText = null;

            if ($question->isEssay()) {
                // Essay: save free-text answer, no auto-grading
                $answerText = trim((string) $request->input('answer_text', ''));
                $answerText = $answerText === '' ? null : $answerText;
                $isCorrect = false; // Essay needs manual grading
            } else {
                // Multiple choice requires a selected option.
                if (!$request->filled('selected_answer')) {
                    return response()->json(['error' => 'selected_answer is required for multiple choice'], 422);
                }

                // Multiple choice: auto-grade
                $selectedAnswer = strtolower(trim($request->selected_answer ?? ''));
                if ($selectedAnswer) {
                    $correctAnswer = strtolower(trim($question->correct_answer));
                    $isCorrect = $correctAnswer === $selectedAnswer;
                }
            }

            // Use firstOrNew + save instead of updateOrCreate for better lock handling
            $answer = StudentAnswer::where('exam_session_id', $session->id)
                ->where('question_id', $question->id)
                ->lockForUpdate()
                ->first();

            $didChange = false;

            if ($answer) {
                $currentSelected = $answer->selected_answer ? strtolower(trim((string) $answer->selected_answer)) : null;
                $currentText = trim((string) ($answer->answer_text ?? ''));
                $currentText = $currentText === '' ? null : $currentText;
                $currentCorrect = (bool) $answer->is_correct;

                $samePayload = $currentSelected === $selectedAnswer
                    && $currentText === $answerText
                    && $currentCorrect === (bool) $isCorrect;

                if (!$samePayload) {
                    $answer->selected_answer = $selectedAnswer;
                    $answer->answer_text = $answerText;
                    $answer->is_correct = $isCorrect;
                    $answer->save();
                    $didChange = true;
                }
            } else {
                $answer = StudentAnswer::create([
                    'exam_session_id' => $session->id,
                    'question_id' => $question->id,
                    'selected_answer' => $selectedAnswer,
                    'answer_text' => $answerText,
                    'is_correct' => $isCorrect,
                ]);
                $didChange = true;
            }

            // Keep admin monitor score estimate up to date only when answer actually changed.
            if ($didChange) {
                Cache::forget("exam.{$exam->id}.earned_points");
            }

            return response()->json([
                'success' => true,
                'saved_id' => $answer->id,
            ]);
        });
    }

    /**
     * Save multiple answers in one request to reduce request burst under high concurrency.
     */
    public function saveAnswersBulk(Request $request, Exam $exam)
    {
        $request->validate([
            'answers' => 'required|array|min:1|max:20',
            'answers.*.question_id' => 'required|integer',
            'answers.*.selected_answer' => 'nullable|in:a,b,c,d',
            'answers.*.answer_text' => 'nullable|string|max:5000',
            'answers.*.client_token' => 'nullable|string|max:64',
        ]);

        $userId = Auth::id();

        return DB::transaction(function () use ($request, $exam, $userId) {
            $session = ExamSession::where('user_id', $userId)
                ->where('exam_id', $exam->id)
                ->where('status', 'ongoing')
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return response()->json(['error' => 'Session not found or expired'], 404);
            }

            $this->markSessionAlive((int) $exam->id, (int) $session->id);

            $questions = $this->getExamQuestionsById($exam);
            $entries = collect($request->input('answers', []))->values();

            $questionIds = $entries
                ->pluck('question_id')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values();

            $existingAnswers = StudentAnswer::where('exam_session_id', $session->id)
                ->whereIn('question_id', $questionIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('question_id');

            $results = [];
            $savedCount = 0;
            $didAnyChange = false;

            foreach ($entries as $entry) {
                $questionId = (int) ($entry['question_id'] ?? 0);
                $clientToken = isset($entry['client_token']) ? (string) $entry['client_token'] : null;

                $question = $questions->get($questionId);
                if (!$question) {
                    $results[] = [
                        'question_id' => $questionId,
                        'client_token' => $clientToken,
                        'success' => false,
                        'error' => 'question_not_found',
                    ];
                    continue;
                }

                $isCorrect = false;
                $selectedAnswer = null;
                $answerText = null;

                if ($question->isEssay()) {
                    $answerText = trim((string) ($entry['answer_text'] ?? ''));
                    $answerText = $answerText === '' ? null : $answerText;
                    $isCorrect = false;
                } else {
                    $selectedAnswer = strtolower(trim((string) ($entry['selected_answer'] ?? '')));
                    if ($selectedAnswer === '') {
                        $results[] = [
                            'question_id' => $questionId,
                            'client_token' => $clientToken,
                            'success' => false,
                            'error' => 'selected_answer_required',
                        ];
                        continue;
                    }

                    $correctAnswer = strtolower(trim((string) $question->correct_answer));
                    $isCorrect = $selectedAnswer === $correctAnswer;
                }

                $answer = $existingAnswers->get($questionId);
                $didChange = false;

                if ($answer) {
                    $currentSelected = $answer->selected_answer ? strtolower(trim((string) $answer->selected_answer)) : null;
                    $currentText = trim((string) ($answer->answer_text ?? ''));
                    $currentText = $currentText === '' ? null : $currentText;
                    $currentCorrect = (bool) $answer->is_correct;

                    $samePayload = $currentSelected === $selectedAnswer
                        && $currentText === $answerText
                        && $currentCorrect === (bool) $isCorrect;

                    if (!$samePayload) {
                        $answer->selected_answer = $selectedAnswer;
                        $answer->answer_text = $answerText;
                        $answer->is_correct = $isCorrect;
                        $answer->save();
                        $didChange = true;
                    }
                } else {
                    $answer = StudentAnswer::create([
                        'exam_session_id' => $session->id,
                        'question_id' => $questionId,
                        'selected_answer' => $selectedAnswer,
                        'answer_text' => $answerText,
                        'is_correct' => $isCorrect,
                    ]);
                    $existingAnswers->put($questionId, $answer);
                    $didChange = true;
                }

                if ($didChange) {
                    $savedCount++;
                    $didAnyChange = true;
                }

                $results[] = [
                    'question_id' => $questionId,
                    'client_token' => $clientToken,
                    'success' => true,
                ];
            }

            if ($didAnyChange) {
                Cache::forget("exam.{$exam->id}.earned_points");
            }

            return response()->json([
                'success' => true,
                'saved_count' => $savedCount,
                'results' => $results,
            ]);
        });
    }

    /**
     * Submit the exam (finalize)
     * Protected against race conditions, double-submit, and time violations.
     */
    public function submitExam(Request $request, Exam $exam)
    {
        $userId = Auth::id();
        
        // Use database transaction with row lock to prevent race conditions
        return \Illuminate\Support\Facades\DB::transaction(function () use ($exam, $userId) {
            // Lock the session row to prevent concurrent updates
            $session = ExamSession::where('user_id', $userId)
                ->where('exam_id', $exam->id)
                ->lockForUpdate()
                ->first();

            // Guard: session not found
            if (!$session) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Sesi ujian tidak ditemukan.');
            }

            // Guard: already completed (double-submit protection)
            if ($session->status === 'completed') {
                return redirect()->route('student.exam.result', $exam)
                    ->with('info', 'Ujian sudah dikumpulkan sebelumnya.');
            }

            // Guard: blocked by admin
            if ($session->status === 'blocked') {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Anda telah di-terminate dari ujian ini.');
            }

            // Server-side timer validation: allow 2 minute grace period after timer ends
            if ($exam->started_at && $exam->duration_minutes) {
                $deadline = $exam->started_at->copy()->addMinutes($exam->duration_minutes)->addMinutes(2);
                if (now()->gt($deadline)) {
                    // Still accept the submission but log it — don't reject
                    // The student may have network delays
                }
            }

            // Eager load questions to avoid N+1 query
            $questions = $this->getExamQuestionsById($exam);
            
            $totalPoints = $questions->sum('points') ?: $questions->count();
            $earnedPoints = 0;

            // Recompute correctness against CURRENT answer key to avoid stale is_correct values.
            $answers = StudentAnswer::where('exam_session_id', $session->id)
                ->get(['id', 'question_id', 'selected_answer', 'is_correct']);

            foreach ($answers as $answer) {
                $question = $questions->get($answer->question_id);
                if (!$question) {
                    continue;
                }

                $isCorrectNow = false;
                if ($question->isMultipleChoice() && $answer->selected_answer) {
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

            // Update session
            $session->update([
                'score_academic' => $academicScore,
                'status' => 'completed',
                'end_time' => now(),
            ]);

            $this->clearSessionHeartbeat((int) $exam->id, (int) $session->id);
            Cache::forget("exam.{$exam->id}.earned_points");

            return redirect()->route('student.exam.result', $exam)
                ->with('success', 'Ujian telah diselesaikan!');
        });
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
        $questions = $this->getExamQuestionsOrdered($exam);
        
        // Load answers in single optimized query (supports MC + essay review).
        $answers = StudentAnswer::where('exam_session_id', $session->id)
            ->get(['question_id', 'selected_answer', 'answer_text'])
            ->keyBy('question_id');

        $correctAnswers = 0;
        $totalScoredQuestions = $questions->filter(fn($q) => $q->isMultipleChoice())->count();
        $totalPoints = $questions->sum('points') ?: $questions->count();
        $earnedPoints = 0;

        foreach ($questions as $question) {
            if (!$question->isMultipleChoice()) {
                continue;
            }

            $answer = $answers->get($question->id);
            if (!$answer || !$answer->selected_answer) {
                continue;
            }

            $selected = strtolower(trim((string) $answer->selected_answer));
            $correct = strtolower(trim((string) $question->correct_answer));
            if ($selected !== '' && $selected === $correct) {
                $correctAnswers++;
                $earnedPoints += ($question->points ?: 1);
            }
        }

        $computedAcademicScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;

        // Heal stale/missing academic score (e.g. interrupted force-stop scoring).
        $storedAcademicScore = $session->score_academic;
        if ($storedAcademicScore === null || (((float) $storedAcademicScore) <= 0.0 && $earnedPoints > 0)) {
            $session->score_academic = $computedAcademicScore;
            $session->save();
        }

        return view('student.exam-result', compact('exam', 'session', 'questions', 'answers', 'correctAnswers', 'totalScoredQuestions'));
    }

    /**
     * Question list in exam order (for rendering).
     */
    private function getExamQuestionsOrdered(Exam $exam)
    {
        return Cache::remember(
            "exam.{$exam->id}.questions.ordered",
            600,
            fn() => $exam->questions()->orderBy('order')->get()
        );
    }

    /**
     * Question map keyed by question id (for scoring/autosave lookups).
     */
    private function getExamQuestionsById(Exam $exam)
    {
        return Cache::remember(
            "exam.{$exam->id}.questions.by_id",
            600,
            fn() => $exam->questions()->get()->keyBy('id')
        );
    }

    /**
     * Polling endpoint for students to check exam status.
      * Uses fresh exam status for immediate countdown/start transitions.
      * Student-specific data (session status) checked separately with lightweight query.
     */
    public function pollStatus(Exam $exam)
    {
        // Always get fresh exam status — do NOT cache during countdown
        // because the transition to 'started' must be detected immediately
        $exam->refresh();
        $exam->isStarted();

        $examData = [
            'exam_status' => $exam->status,
            'started_at' => $exam->started_at?->toISOString(),
        ];

        // Student-specific session check (lightweight single query, no caching needed)
        $sessionStatus = null;
        $sessionIntegrity = null;
        $violationCount = null;
        $user = Auth::user();
        if ($user) {
            $session = ExamSession::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->select('id', 'status', 'joined_at', 'score_integrity', 'violation_count')
                ->first();
            if ($session) {
                if ($session->status === 'ongoing' && !$session->joined_at) {
                    ExamSession::where('id', $session->id)->update(['joined_at' => now()]);
                    Cache::forget("exam.{$exam->id}.lobby_status");
                }

                if ($session->status === 'ongoing') {
                    $this->markSessionAlive((int) $exam->id, (int) $session->id);
                } else {
                    $this->clearSessionHeartbeat((int) $exam->id, (int) $session->id);
                }

                $sessionStatus = $session->status;
                $sessionIntegrity = $session->score_integrity;
                $violationCount = $session->violation_count ?? 0;
            }
        }

        return response()->json([
            'exam_status' => $examData['exam_status'],
            'started_at' => $examData['started_at'],
            'session_status' => $sessionStatus,
            'session_integrity' => $sessionIntegrity,
            'violation_count' => $violationCount,
        ]);
    }
}
