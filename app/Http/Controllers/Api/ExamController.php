<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExamController extends Controller
{
    public function startExam(Request $request, $exam_id)
    {
        // Cek apakah user sudah punya sesi yang belum selesai?
        $existingSession = ExamSession::where('user_id', Auth::id())
                            ->where('exam_id', $exam_id)
                            ->first();

        if ($existingSession) {
            // Resume session (User refresh/balik lagi)
            return response()->json([
                'message' => 'Resuming session',
                'session' => $existingSession
            ]);
        }

        // Create New Session
        $session = ExamSession::create([
            'user_id' => Auth::id(),
            'exam_id' => $exam_id,
            'start_time' => now(),
            'score_integrity' => 100 // Modal awal 100
        ]);

        return response()->json([
            'message' => 'Exam started',
            'session' => $session
        ]);
    }

    // Logic submit jawaban & finish exam disesuaikan kebutuhan soal
}