<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\IntegrityController;

Route::middleware(['auth:sanctum'])->group(function () {
    // 1. Mulai Ujian (Create Session)
    Route::post('/exam/{exam_id}/start', [ExamController::class, 'startExam']);

    // 2. Submit Jawaban (Per soal atau bulk)
    Route::post('/exam/submit-answer', [ExamController::class, 'submitAnswer']);

    // 3. Selesai Ujian
    Route::post('/exam/finish', [ExamController::class, 'finishExam']);

    // --- ANTI CHEAT ENDPOINTS ---
    // 4. Lapor Pelanggaran (Teman frontend nembak kesini kalau JS detect sesuatu)
    Route::post('/integrity/log-violation', [IntegrityController::class, 'logViolation']);
    
    // 5. Cek Status Terkini (Untuk update UI skor integritas di HP siswa)
    Route::get('/integrity/status/{session_id}', [IntegrityController::class, 'getStatus']);
});
