<?php

namespace App\Http\Controllers;

use App\Models\Exam;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $exams = Exam::where('is_active', true)
            ->whereIn('status', ['lobby', 'started'])
            ->get();

        return view('student.dashboard', compact('exams'));
    }
}
