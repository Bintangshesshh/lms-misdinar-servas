<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $exams = Exam::where('is_active', true)
            ->whereIn('status', ['lobby', 'started', 'finished'])
            ->with(['sessions' => function ($q) {
                $q->where('user_id', Auth::id());
            }])
            ->get();

        return view('student.dashboard', compact('exams'));
    }
}
