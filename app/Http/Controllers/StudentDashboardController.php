<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // No cache — must always show current exam status (lobby open/closed)
        $exams = Exam::where('is_active', true)
            ->whereIn('status', ['lobby', 'started', 'countdown', 'finished'])
            ->with(['sessions' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->get();

        return view('student.dashboard', compact('exams'));
    }
}
