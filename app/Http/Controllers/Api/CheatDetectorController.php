<?php

// app/Http/Controllers/Api/CheatDetectorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class CheatDetectorController extends Controller
{
    public function logViolation(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'action'     => 'required|string',
            'duration'   => 'numeric|min:0' // Durasi dalam detik
        ]);

        $session = ExamSession::findOrFail($request->session_id);

        // LOGIKA PENGURANGAN NILAI (INTEGRITY)
        $penalty = 0;
        
        // Aturan:
        // - Keluar < 3 detik (Grace Period) = 0 Poin
        // - Keluar > 3 detik = 1 Poin per detik
        // - Split Screen (Blur) = Langsung potong 5 poin
        
        if ($request->action === 'tab_switch' || $request->action === 'app_background') {
            if ($request->duration > 3) {
                $penalty = 1 + floor($request->duration / 5); // Contoh rumus
            }
        } elseif ($request->action === 'window_blur') {
             $penalty = 5;
        }

        // Update Skor (Tidak boleh minus)
        $newIntegrity = max(0, $session->score_integrity - $penalty);
        
        $session->update(['score_integrity' => $newIntegrity]);

        // Catat Log Detail
        $session->cheatLogs()->create([
            'violation_type'   => $request->action,
            'duration_seconds' => $request->duration,
            'occurred_at'      => now()
        ]);

        return response()->json([
            'status' => 'recorded', 
            'current_integrity' => $newIntegrity
        ]);
    }
}
