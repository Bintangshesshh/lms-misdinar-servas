<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\CheatLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class IntegrityController extends Controller
{
    public function logViolation(Request $request)
    {
        // 1. Validasi data dari Frontend
        $request->validate([
            'session_id' => 'required|exists:exam_sessions,id',
            'type'       => 'required|string|in:tab_switch,split_screen,window_blur,device_offline,screenshot,fullscreen_exit,resize_suspicion',
            'duration'   => 'required|integer|min:0'
        ]);

        $session = ExamSession::select('id', 'user_id', 'status', 'score_integrity')
            ->findOrFail($request->session_id);

        // Authorization: only the session owner can log violations
        if ($session->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cek status sesi
        if ($session->status !== 'ongoing') {
            return response()->json(['message' => 'Session closed'], 400);
        }

        // 2. HITUNG HUKUMAN (berbeda per jenis pelanggaran)
        $penaltyMap = [
            'tab_switch'       => 25,
            'fullscreen_exit'  => 20,
            'split_screen'     => 20,
            'resize_suspicion' => 15,
            'window_blur'      => 15,
            'screenshot'       => 30,
            'device_offline'   => 10,
        ];
        $penalty = $penaltyMap[$request->type] ?? 20;

        // 3. Update Score Integritas
        $newScore = max(0, $session->score_integrity - $penalty);
        $session->update(['score_integrity' => $newScore]);

        // 4. Simpan Log Detail (Bukti Forensik)
        CheatLog::create([
            'exam_session_id' => $session->id,
            'violation_type'  => $request->type,
            'duration_seconds' => $request->duration,
            'occurred_at'     => now()
        ]);

        // 5. AUTO-TERMINATE jika skor mencapai 0
        $terminated = false;
        if ($newScore <= 0) {
            $session->update(['status' => 'blocked']);
            $terminated = true;
        }

        // Clear ALL poll caches so admin sees updated integrity immediately
        Cache::forget("exam.{$session->exam_id}.lobby_status");
        Cache::forget("exam.{$session->exam_id}.poll_status");

        return response()->json([
            'status' => 'logged',
            'penalty_applied' => $penalty,
            'current_integrity' => $newScore,
            'terminated' => $terminated,
        ]);
    }

    public function getStatus($session_id)
    {
        $session = ExamSession::select('id', 'user_id', 'exam_id', 'score_integrity', 'status')
            ->findOrFail($session_id);

        // Authorization: only the session owner can view status
        if ($session->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'score_integrity' => $session->score_integrity,
            'violation_count' => Cache::remember(
                "session.{$session_id}.violation_count",
                10,
                fn() => $session->cheatLogs()->count()
            ),
            'status' => $session->status,
        ]);
    }
}