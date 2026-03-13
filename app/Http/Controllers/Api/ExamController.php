<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\StudentAnswer;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function startExam(Request $request, $exam_id)
    {
        $exam = Exam::findOrFail($exam_id);

        if (!$exam->is_active) {
            return response()->json(['message' => 'Exam is inactive'], 400);
        }

        if (!in_array($exam->status, ['lobby', 'countdown', 'started'], true)) {
            return response()->json(['message' => 'Exam is not open'], 400);
        }

        // Cek apakah user sudah punya sesi yang belum selesai?
        $existingSession = ExamSession::where('user_id', Auth::id())
            ->where('exam_id', $exam_id)
            ->first();

        if ($existingSession) {
            $updates = [];
            if ($existingSession->status === 'ongoing' && !$existingSession->joined_at) {
                $updates['joined_at'] = now();
            }
            if (!$existingSession->start_time) {
                $updates['start_time'] = now();
            }
            if (!empty($updates)) {
                $existingSession->update($updates);
                Cache::forget("exam.{$exam_id}.lobby_status");
            }

            // Resume session (User refresh/balik lagi)
            return response()->json([
                'message' => 'Resuming session',
                'session' => $existingSession,
            ]);
        }

        try {
            // Create New Session
            $session = ExamSession::create([
                'user_id' => Auth::id(),
                'exam_id' => $exam_id,
                'start_time' => now(),
                'joined_at' => now(),
                'score_integrity' => 100,
                'status' => 'ongoing',
            ]);
        } catch (QueryException $e) {
            // Handle race on unique(user_id, exam_id): return existing session.
            $session = ExamSession::where('user_id', Auth::id())
                ->where('exam_id', $exam_id)
                ->firstOrFail();
        }

        return response()->json([
            'message' => 'Exam started',
            'session' => $session,
            'exam_status' => $exam->status,
        ]);
    }

    /**
     * Submit/save one answer from API client.
     */
    public function submitAnswer(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'question_id' => 'required|exists:questions,id',
            'selected_answer' => 'nullable|in:a,b,c,d',
            'answer_text' => 'nullable|string|max:5000',
        ]);

        return DB::transaction(function () use ($validated) {
            $session = ExamSession::where('id', $validated['session_id'])
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'ongoing') {
                return response()->json(['message' => 'Session closed'], 400);
            }

            $question = Question::where('id', $validated['question_id'])
                ->where('exam_id', $session->exam_id)
                ->firstOrFail();

            $selectedAnswer = null;
            $answerText = null;
            $isCorrect = false;

            if (($question->question_type ?? 'multiple_choice') === 'essay') {
                $answerText = trim((string) ($validated['answer_text'] ?? ''));
                $answerText = $answerText === '' ? null : $answerText;
            } else {
                if (!array_key_exists('selected_answer', $validated) || $validated['selected_answer'] === null) {
                    return response()->json(['message' => 'selected_answer is required for multiple choice'], 422);
                }

                $selectedAnswer = strtolower(trim((string) $validated['selected_answer']));
                $correctAnswer = strtolower(trim((string) $question->correct_answer));
                $isCorrect = $selectedAnswer !== '' && $selectedAnswer === $correctAnswer;
            }

            StudentAnswer::updateOrCreate(
                [
                    'exam_session_id' => $session->id,
                    'question_id' => $question->id,
                ],
                [
                    'selected_answer' => $selectedAnswer,
                    'answer_text' => $answerText,
                    'is_correct' => $isCorrect,
                ]
            );

            $answered = StudentAnswer::where('exam_session_id', $session->id)
                ->where(function ($q) {
                    $q->whereNotNull('selected_answer')->orWhereNotNull('answer_text');
                })
                ->count();

            $total = Question::where('exam_id', $session->exam_id)->count();

            return response()->json([
                'message' => 'Answer saved',
                'answered' => $answered,
                'total' => $total,
            ]);
        });
    }

    /**
     * Finalize exam session from API client.
     */
    public function finishExam(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $session = ExamSession::where('id', $validated['session_id'])
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status === 'completed') {
                return response()->json([
                    'message' => 'Already completed',
                    'score_academic' => $session->score_academic,
                ]);
            }

            if ($session->status === 'blocked') {
                return response()->json(['message' => 'Session blocked'], 400);
            }

            $questions = Cache::remember(
                "exam.{$session->exam_id}.questions.by_id",
                600,
                fn() => Question::where('exam_id', $session->exam_id)->get()->keyBy('id')
            );

            $totalPoints = $questions
                ->filter(fn($q) => ($q->question_type ?? 'multiple_choice') !== 'essay')
                ->sum('points')
                ?: $questions->filter(fn($q) => ($q->question_type ?? 'multiple_choice') !== 'essay')->count();
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

            Cache::forget("exam.{$session->exam_id}.lobby_status");
            Cache::forget("exam.{$session->exam_id}.poll_status");

            return response()->json([
                'message' => 'Exam finished',
                'score_academic' => $academicScore,
                'session_id' => $session->id,
            ]);
        });
    }
}