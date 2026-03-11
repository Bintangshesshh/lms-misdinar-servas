<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ExamLobbyController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

Route::get('/', function () {
    return redirect()->route('login');
});

// Generic dashboard redirect (Breeze default — we redirect by role)
Route::get('/dashboard', function () {
    $user = Auth::user();
    /** @var \App\Models\User|null $user */
    if ($user?->isAdmin()) {
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
    Route::post('/exam/{exam}/save-answer', [ExamLobbyController::class, 'saveAnswer'])
        ->middleware('throttle:exam-save-answer')
        ->name('exam.saveAnswer');
    Route::post('/exam/{exam}/save-answers-bulk', [ExamLobbyController::class, 'saveAnswersBulk'])
        ->middleware('throttle:exam-save-bulk')
        ->name('exam.saveAnswersBulk');
    Route::post('/exam/{exam}/submit', [ExamLobbyController::class, 'submitExam'])->name('exam.submit');
    Route::get('/exam/{exam}/result', [ExamLobbyController::class, 'examResult'])->name('exam.result');
    Route::post('/integrity/log-violation', [\App\Http\Controllers\Api\IntegrityController::class, 'logViolation'])
        ->middleware('throttle:integrity-log')
        ->name('integrity.logViolation');
    Route::get('/integrity/status/{session_id}', [\App\Http\Controllers\Api\IntegrityController::class, 'getStatus'])
        ->middleware('throttle:exam-poll')
        ->name('integrity.status');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Student Management
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/import', [StudentController::class, 'importForm'])->name('students.import');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.importProcess');
    Route::delete('/students/delete-all', [StudentController::class, 'deleteAll'])->name('students.deleteAll');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    // Exam CRUD
    Route::get('/exam/create', [AdminDashboardController::class, 'createExam'])->name('exam.create');
    Route::post('/exam', [AdminDashboardController::class, 'storeExam'])->name('exam.store');
    Route::get('/exam/{exam}/edit', [AdminDashboardController::class, 'editExam'])->name('exam.edit');
    Route::put('/exam/{exam}', [AdminDashboardController::class, 'updateExam'])->name('exam.update');
    Route::delete('/exam/{exam}', [AdminDashboardController::class, 'deleteExam'])->name('exam.delete');
    Route::post('/exam/{exam}/reset', [AdminDashboardController::class, 'resetExam'])->name('exam.reset');

    Route::get('/exam/{exam}/monitor', [AdminDashboardController::class, 'monitor'])->name('exam.monitor');
    Route::post('/exam/{exam}/open-lobby', [AdminDashboardController::class, 'openLobby'])->name('exam.openLobby');
    Route::post('/exam/{exam}/start', [AdminDashboardController::class, 'startExam'])->name('exam.start');

    // Admin monitoring actions
    Route::post('/exam/{exam}/terminate-student', [AdminDashboardController::class, 'terminateStudent'])->name('exam.terminateStudent');
    Route::post('/exam/{exam}/reinstate-student', [AdminDashboardController::class, 'reinstateStudent'])->name('exam.reinstateStudent');
    Route::post('/exam/{exam}/stop', [AdminDashboardController::class, 'stopExam'])->name('exam.stop');
    Route::get('/exam/{exam}/export', [AdminDashboardController::class, 'exportResult'])->name('exam.export');

    // Polling endpoints (JSON) - Higher rate limit for monitoring
    Route::get('/exam/{exam}/lobby-status', [AdminDashboardController::class, 'lobbyStatus'])
        ->middleware('throttle:admin-monitor')
        ->name('exam.lobbyStatus');

    // Question management
    Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/{exam}', [QuestionController::class, 'show'])->name('questions.show');
    Route::get('/questions/{exam}/create', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions/{exam}', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{exam}/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{exam}/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{exam}/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('/questions/{exam}/set-all-points', [QuestionController::class, 'setAllPoints'])->name('questions.setAllPoints');
    Route::get('/questions/{exam}/import', [QuestionController::class, 'importForm'])->name('questions.import');
    Route::post('/questions/{exam}/import', [QuestionController::class, 'import'])->name('questions.importProcess');
});

// Student polling endpoint - No group throttle (polling is already cached server-side)
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/api/exam/{exam}/poll-status', [ExamLobbyController::class, 'pollStatus'])
        ->middleware('throttle:exam-poll')
        ->name('exam.pollStatus');
});

require __DIR__.'/auth.php';
