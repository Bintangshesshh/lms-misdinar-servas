<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // Cache student exam list for 30 seconds
        $exams = Cache::remember("student.{$userId}.exams", 30, function () use ($userId) {
            return Exam::where('is_active', true)
                ->whereIn('status', ['lobby', 'started', 'finished'])
                ->with(['sessions' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }])
                ->get();
        });

        return view('student.dashboard', compact('exams'));
    }
}
