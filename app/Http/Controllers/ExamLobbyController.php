<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
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

        return view('student.exam-take', compact('exam', 'session'));
    }

    // Polling endpoint for students to check exam status
    public function pollStatus(Exam $exam)
    {
        $exam->refresh();

        return response()->json([
            'exam_status' => $exam->status,
            'started_at' => $exam->started_at?->toISOString(),
        ]);
    }
}
