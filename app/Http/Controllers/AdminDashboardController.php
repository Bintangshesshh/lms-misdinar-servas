<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $exams = Exam::withCount(['sessions'])->get();
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
        // Set to countdown first (frontend will show 5..4..3..2..1)
        $exam->update([
            'status' => 'countdown',
            'started_at' => now()->addSeconds(5),
        ]);

        // After 5 seconds, update status to "started"
        // Using dispatch afterResponse for simplicity
        dispatch(function () use ($exam) {
            sleep(5);
            $exam->update(['status' => 'started']);
        })->afterResponse();

        return back()->with('success', 'Countdown dimulai! Ujian akan dimulai dalam 5 detik.');
    }

    // JSON endpoint for admin polling
    public function lobbyStatus(Exam $exam)
    {
        $students = $exam->sessions()
            ->whereNotNull('joined_at')
            ->with('user:id,name,email')
            ->get()
            ->map(fn($s) => [
                'id' => $s->user->id,
                'name' => $s->user->name,
                'email' => $s->user->email,
                'joined_at' => $s->joined_at->diffForHumans(),
                'integrity' => $s->score_integrity,
            ]);

        return response()->json([
            'exam_status' => $exam->status,
            'student_count' => $students->count(),
            'students' => $students,
        ]);
    }
}
