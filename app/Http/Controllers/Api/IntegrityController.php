<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\CheatLog;
use Illuminate\Http\Request;

class IntegrityController extends Controller
{
    public function logViolation(Request $request)
    {
        // 1. Validasi data dari Frontend
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'type'       => 'required|string|in:tab_switch,split_screen,window_blur,device_offline,screenshot',
            'duration'   => 'required|integer|min:0' // Durasi dalam detik
        ]);

        $session = ExamSession::findOrFail($request->session_id);

        // Cek status sesi (kalau sudah selesai, tolak log)
        if ($session->status !== 'ongoing') {
            return response()->json(['message' => 'Session closed'], 400);
        }

        // 2. HITUNG HUKUMAN (PENALTY LOGIC)
        // Setiap pelanggaran langsung -30 poin, tanpa grace period
        $penalty = 30;
        $duration = $request->duration;

        // 3. Update Score Integritas di Database
        // Score tidak boleh minus (max 0)
        $newScore = max(0, $session->score_integrity - $penalty);
        $session->update(['score_integrity' => $newScore]);

        // 4. Simpan Log Detail (Bukti Forensik)
        CheatLog::create([
            'exam_session_id' => $session->id,
            'violation_type'  => $request->type,
            'duration_seconds' => $duration,
            'occurred_at'     => now()
        ]);

        // 5. AUTO-TERMINATE jika skor mencapai 0
        $terminated = false;
        if ($newScore <= 0) {
            $session->update(['status' => 'blocked']);
            $terminated = true;
        }

        // 6. Kembalikan sisa skor ke frontend
        return response()->json([
            'status' => 'logged',
            'penalty_applied' => $penalty,
            'current_integrity' => $newScore,
            'terminated' => $terminated,
        ]);
    }

    public function getStatus($session_id)
    {
        $session = ExamSession::findOrFail($session_id);
        return response()->json([
            'score_integrity' => $session->score_integrity,
            'violation_count' => $session->cheatLogs()->count(),
            'status' => $session->status,
        ]);
    }
}