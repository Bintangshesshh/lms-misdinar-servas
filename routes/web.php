<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ExamLobbyController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

// Generic dashboard redirect (Breeze default — we redirect by role)
Route::get('/dashboard', function () {
    if (Auth::check() && Auth::user()?->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('student.dashboard');
})->middleware(['auth'])->name('dashboard');

// Profile (from Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/exam/{exam}/lobby', [ExamLobbyController::class, 'studentLobby'])->name('exam.lobby');
    Route::post('/exam/{exam}/join', [ExamLobbyController::class, 'joinLobby'])->name('exam.join');
    Route::get('/exam/{exam}/take', [ExamLobbyController::class, 'takeExam'])->name('exam.take');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/exam/{exam}/monitor', [AdminDashboardController::class, 'monitor'])->name('exam.monitor');
    Route::post('/exam/{exam}/open-lobby', [AdminDashboardController::class, 'openLobby'])->name('exam.openLobby');
    Route::post('/exam/{exam}/start', [AdminDashboardController::class, 'startExam'])->name('exam.start');

    // Polling endpoints (JSON)
    Route::get('/exam/{exam}/lobby-status', [AdminDashboardController::class, 'lobbyStatus'])->name('exam.lobbyStatus');
});

// Student polling endpoint
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/api/exam/{exam}/poll-status', [ExamLobbyController::class, 'pollStatus'])->name('exam.pollStatus');
});

require __DIR__.'/auth.php';
