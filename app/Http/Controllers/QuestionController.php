<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;

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
}
