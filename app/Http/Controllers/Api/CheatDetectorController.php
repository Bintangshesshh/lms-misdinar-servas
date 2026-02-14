<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExamSession;
use App\Models\CheatLog;

class CheatDetectorController extends Controller
{
    public function logViolation(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id' ,
            'type' => 'required|string'
        ]);

        $session = ExamSession::findOrFail($request->session_id);
        if ($session->status !== 'ongoing') 
            {
                return response()->json(['status' => 'ignored', 'message' => 'Exam already closed']);
            }

        CheatLog::create([
            'exam_session_id' => $session->id,
            'violation_type' => $request->type,
            'occured_at' => now(),
        ]);

        $session->increment('violation_count');

        $maxToleransi = 3;

        if($session->violation_count >= $maxToleransi)
            {
                $session->update(['status' => 'blocked']);

                return response()->json([
                    'action' => 'terminate',
                    'message' => 'Anda terdeteksi melakukan kecurangan berulang kali. Ujian dihentikan untukmu'
                ]);
            }
    }
}
