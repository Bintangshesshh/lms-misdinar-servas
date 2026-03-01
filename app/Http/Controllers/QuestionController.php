<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string|max:500',
            'option_b' => 'required|string|max:500',
            'option_c' => 'required|string|max:500',
            'option_d' => 'required|string|max:500',
            'correct_answer' => 'required|in:a,b,c,d',
            'points' => 'required|integer|min:1|max:100',
        ]);

        $validated['exam_id'] = $exam->id;
        $validated['order'] = $exam->questions()->count() + 1;

        Question::create($validated);

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
        $validated = $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string|max:500',
            'option_b' => 'required|string|max:500',
            'option_c' => 'required|string|max:500',
            'option_d' => 'required|string|max:500',
            'correct_answer' => 'required|in:a,b,c,d',
            'points' => 'required|integer|min:1|max:100',
        ]);

        $question->update($validated);

        return redirect()->route('admin.questions.show', $exam)
            ->with('success', 'Soal berhasil diperbarui!');
    }

    /**
     * Delete question.
     */
    public function destroy(Exam $exam, Question $question)
    {
        $question->delete();

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
     * Format: question_text,option_a,option_b,option_c,option_d,correct_answer,points (one per line)
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
                
                // Minimum: question_text + 4 options + correct_answer
                if (count($parts) < 6) {
                    $errors[] = "Baris {$lineNum}: Minimal 6 kolom (soal, opsi_a, opsi_b, opsi_c, opsi_d, jawaban)";
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
}
