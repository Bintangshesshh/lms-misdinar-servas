<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    /**
     * List all exams for question management.
     */
    public function index()
    {
        $exams = Exam::withCount('questions')->with('questions')->get();
        return view('admin.questions.index', compact('exams'));
    }

    /**
     * Show questions for a specific exam.
     */
    public function show(Exam $exam)
    {
        $exam->load(['questions' => fn($q) => $q->orderBy('order')]);
        return view('admin.questions.show', compact('exam'));
    }

    /**
     * Show form to create a new question.
     */
    public function create(Exam $exam)
    {
        return view('admin.questions.create', compact('exam'));
    }

    /**
     * Store a new question.
     */
    public function store(Request $request, Exam $exam)
    {
        $questionType = $request->input('question_type', 'multiple_choice');

        if ($questionType === 'essay') {
            $validated = $request->validate([
                'question_text' => 'required|string',
                'question_type' => 'required|in:multiple_choice,essay',
                'points' => 'required|integer|min:1|max:100',
            ]);
            $validated['option_a'] = null;
            $validated['option_b'] = null;
            $validated['option_c'] = null;
            $validated['option_d'] = null;
            $validated['correct_answer'] = null;
        } else {
            $validated = $request->validate([
                'question_text' => 'required|string',
                'question_type' => 'required|in:multiple_choice,essay',
                'option_a' => 'required|string|max:500',
                'option_b' => 'required|string|max:500',
                'option_c' => 'required|string|max:500',
                'option_d' => 'required|string|max:500',
                'correct_answer' => 'required|in:a,b,c,d',
                'points' => 'required|integer|min:1|max:100',
            ]);
        }

        $validated['exam_id'] = $exam->id;
        $validated['order'] = $exam->questions()->count() + 1;

        // Prevent duplicate on double-submit (same question_text + exam within 10 seconds)
        $exists = Question::where('exam_id', $exam->id)
            ->where('question_text', $validated['question_text'])
            ->where('created_at', '>=', now()->subSeconds(10))
            ->exists();

        if (!$exists) {
            Question::create($validated);
            $this->clearExamQuestionCaches($exam);
        }

        return redirect()->route('admin.questions.show', $exam)
            ->with('success', 'Soal berhasil ditambahkan!');
    }

    /**
     * Show edit form.
     */
    public function edit(Exam $exam, Question $question)
    {
        return view('admin.questions.edit', compact('exam', 'question'));
    }

    /**
     * Update question.
     */
    public function update(Request $request, Exam $exam, Question $question)
    {
        $questionType = $request->input('question_type', 'multiple_choice');

        if ($questionType === 'essay') {
            $validated = $request->validate([
                'question_text' => 'required|string',
                'question_type' => 'required|in:multiple_choice,essay',
                'points' => 'required|integer|min:1|max:100',
            ]);
            $validated['option_a'] = null;
            $validated['option_b'] = null;
            $validated['option_c'] = null;
            $validated['option_d'] = null;
            $validated['correct_answer'] = null;
        } else {
            $validated = $request->validate([
                'question_text' => 'required|string',
                'question_type' => 'required|in:multiple_choice,essay',
                'option_a' => 'required|string|max:500',
                'option_b' => 'required|string|max:500',
                'option_c' => 'required|string|max:500',
                'option_d' => 'required|string|max:500',
                'correct_answer' => 'required|in:a,b,c,d',
                'points' => 'required|integer|min:1|max:100',
            ]);
        }

        $question->update($validated);
        $this->clearExamQuestionCaches($exam);

        return redirect()->route('admin.questions.show', $exam)
            ->with('success', 'Soal berhasil diperbarui!');
    }

    /**
     * Delete question.
     */
    public function destroy(Exam $exam, Question $question)
    {
        $question->delete();
        $this->clearExamQuestionCaches($exam);

        return redirect()->route('admin.questions.show', $exam)
            ->with('success', 'Soal berhasil dihapus!');
    }

    /**
     * Set all questions in an exam to the same points.
     */
    public function setAllPoints(Request $request, Exam $exam)
    {
        $request->validate([
            'points' => 'required|integer|min:1|max:100',
        ]);

        $exam->questions()->update(['points' => $request->points]);
        $this->clearExamQuestionCaches($exam);

        return redirect()->route('admin.questions.show', $exam)
            ->with('success', "Semua soal diset ke {$request->points} poin!");
    }

    /**
     * Show bulk import form.
     */
    public function importForm(Exam $exam)
    {
        return view('admin.questions.import', compact('exam'));
    }

    /**
     * Process bulk import from text input.
     * Format MC: question_text,option_a,option_b,option_c,option_d,correct_answer,points
     * Format Essay: ESSAY,question_text,points
     * One per line.
     */
    public function import(Request $request, Exam $exam)
    {
        $request->validate([
            'data' => 'required|string',
            'default_points' => 'nullable|integer|min:1|max:100',
        ]);

        $defaultPoints = $request->default_points ?: 10;
        $lines = array_filter(array_map('trim', explode("\n", $request->data)));
        
        $imported = 0;
        $errors = [];
        $currentOrder = $exam->questions()->count();

        DB::beginTransaction();
        try {
            foreach ($lines as $index => $line) {
                $lineNum = $index + 1;
                $parts = array_map('trim', str_getcsv($line));
                
                // Check if essay format: first column is "ESSAY"
                if (strtoupper($parts[0] ?? '') === 'ESSAY') {
                    if (count($parts) < 2 || empty($parts[1])) {
                        $errors[] = "Baris {$lineNum}: Format essay: ESSAY,teks_soal,poin";
                        continue;
                    }
                    $points = !empty($parts[2]) ? (int) $parts[2] : $defaultPoints;
                    if ($points < 1 || $points > 100) $points = $defaultPoints;

                    $currentOrder++;
                    Question::create([
                        'exam_id' => $exam->id,
                        'question_text' => $parts[1],
                        'question_type' => 'essay',
                        'option_a' => null,
                        'option_b' => null,
                        'option_c' => null,
                        'option_d' => null,
                        'correct_answer' => null,
                        'points' => $points,
                        'order' => $currentOrder,
                    ]);
                    $imported++;
                    continue;
                }

                // Multiple choice format
                if (count($parts) < 6) {
                    $errors[] = "Baris {$lineNum}: Minimal 6 kolom (soal, opsi_a, opsi_b, opsi_c, opsi_d, jawaban) atau format ESSAY,teks_soal,poin";
                    continue;
                }

                if (empty($parts[0])) {
                    $errors[] = "Baris {$lineNum}: Teks soal tidak boleh kosong";
                    continue;
                }

                $correctAnswer = strtolower(trim($parts[5]));
                if (!in_array($correctAnswer, ['a', 'b', 'c', 'd'])) {
                    $errors[] = "Baris {$lineNum}: Jawaban benar harus a, b, c, atau d (ditemukan: '{$parts[5]}')";
                    continue;
                }

                $points = !empty($parts[6]) ? (int) $parts[6] : $defaultPoints;
                if ($points < 1 || $points > 100) {
                    $points = $defaultPoints;
                }

                $currentOrder++;

                Question::create([
                    'exam_id' => $exam->id,
                    'question_text' => $parts[0],
                    'question_type' => 'multiple_choice',
                    'option_a' => $parts[1],
                    'option_b' => $parts[2],
                    'option_c' => $parts[3],
                    'option_d' => $parts[4],
                    'correct_answer' => $correctAnswer,
                    'points' => $points,
                    'order' => $currentOrder,
                ]);

                $imported++;
            }

            DB::commit();
            if ($imported > 0) {
                $this->clearExamQuestionCaches($exam);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }

        $message = "{$imported} soal berhasil diimport.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " baris error.";
        }

        return redirect()->route('admin.questions.show', $exam)
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    /**
     * Clear all caches derived from exam question data.
     */
    private function clearExamQuestionCaches(Exam $exam): void
    {
        Cache::forget("exam.{$exam->id}.questions");
        Cache::forget("exam.{$exam->id}.questions.ordered");
        Cache::forget("exam.{$exam->id}.questions.by_id");
        Cache::forget("exam.{$exam->id}.total_questions");
        Cache::forget("exam.{$exam->id}.total_points");
        Cache::forget("exam.{$exam->id}.question_points");
        Cache::forget("exam.{$exam->id}.lobby_status");
        Cache::forget("exam.{$exam->id}.poll_status");
    }
}
