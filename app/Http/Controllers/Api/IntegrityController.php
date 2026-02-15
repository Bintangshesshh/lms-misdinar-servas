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
            'type'       => 'required|string|in:tab_switch,split_screen,dnd_off,device_offline',
            'duration'   => 'required|integer|min:0' // Durasi dalam detik
        ]);

        $session = ExamSession::findOrFail($request->session_id);

        // Cek status sesi (kalau sudah selesai, tolak log)
        if ($session->status !== 'ongoing') {
            return response()->json(['message' => 'Session closed'], 400);
        }

        // 2. HITUNG HUKUMAN (PENALTY LOGIC)
        $penalty = 0;
        $duration = $request->duration;

        switch ($request->type) {
            case 'tab_switch':
                // Grace period 3 detik (dianggap kepencet/notif lewat)
                if ($duration > 3 && $duration <= 10) $penalty = 2;   // Ringan
                elseif ($duration > 10 && $duration <= 60) $penalty = 10; // Sedang (Googling)
                elseif ($duration > 60) $penalty = 25; // Berat (ChatGPT/Brainly)
                break;

            case 'split_screen':
                $penalty = 20; // Langsung potong besar karena niat curang tinggi
                break;

            case 'dnd_off':
                $penalty = 5; // Hukuman karena mematikan mode DND di tengah jalan
                break;
        }

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

        // 5. Kembalikan sisa skor ke frontend (biar UI berubah merah/hijau)
        return response()->json([
            'status' => 'logged',
            'penalty_applied' => $penalty,
            'current_integrity' => $newScore
        ]);
    }

    public function getStatus($session_id)
    {
        $session = ExamSession::findOrFail($session_id);
        return response()->json([
            'score_integrity' => $session->score_integrity,
            'violation_count' => $session->cheatLogs()->count()
        ]);
    }
}