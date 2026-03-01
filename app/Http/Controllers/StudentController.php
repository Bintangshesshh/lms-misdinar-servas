<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ExamSession;
use App\Models\StudentAnswer;
use App\Models\CheatLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * Display all students.
     */
    public function index()
    {
        $students = User::where('role', 'student')
            ->orderBy('name')
            ->get();
        
        return view('admin.students.index', compact('students'));
    }

    /**
     * Show form for creating a new student.
     */
    public function create()
    {
        return view('admin.students.create');
    }

    /**
     * Store a new student.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:users,name',
            'full_name' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'kelas' => 'nullable|string|max:50',
            'umur' => 'nullable|integer|min:5|max:100',
            'lingkungan' => 'nullable|string|max:100',
            'asal_sekolah' => 'nullable|string|max:150',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'student';

        User::create($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'Siswa berhasil ditambahkan!');
    }

    /**
     * Show edit form.
     */
    public function edit(User $student)
    {
        return view('admin.students.edit', compact('student'));
    }

    /**
     * Update student data.
     */
    public function update(Request $request, User $student)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($student->id)],
            'full_name' => 'nullable|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($student->id)],
            'password' => 'nullable|string|min:6',
            'kelas' => 'nullable|string|max:50',
            'umur' => 'nullable|integer|min:5|max:100',
            'lingkungan' => 'nullable|string|max:100',
            'asal_sekolah' => 'nullable|string|max:150',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $student->update($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'Data siswa berhasil diperbarui!');
    }

    /**
     * Delete a student.
     */
    public function destroy(User $student)
    {
        $name = $student->name;

        // Delete related records to avoid FK constraint errors
        $sessionIds = ExamSession::where('user_id', $student->id)->pluck('id');
        if ($sessionIds->isNotEmpty()) {
            StudentAnswer::whereIn('exam_session_id', $sessionIds)->delete();
            CheatLog::whereIn('exam_session_id', $sessionIds)->delete();
            ExamSession::whereIn('id', $sessionIds)->delete();
        }

        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('success', "Siswa '{$name}' berhasil dihapus!");
    }

    /**
     * Show bulk import form.
     */
    public function importForm()
    {
        return view('admin.students.import');
    }

    /**
     * Process bulk import from text input.
     * Format: name,full_name,email,password,kelas,lingkungan,asal_sekolah (one per line)
     */
    public function import(Request $request)
    {
        $request->validate([
            'data' => 'required|string',
            'default_password' => 'nullable|string|min:6',
        ]);

        $defaultPassword = $request->default_password ?: 'password123';
        $lines = array_filter(array_map('trim', explode("\n", $request->data)));
        
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($lines as $index => $line) {
                $lineNum = $index + 1;
                $parts = array_map('trim', str_getcsv($line));
                
                // Minimum: name only
                if (empty($parts[0])) {
                    $errors[] = "Baris {$lineNum}: Nama tidak boleh kosong";
                    continue;
                }

                $name = $parts[0];
                $fullName = $parts[1] ?? $name;
                $email = $parts[2] ?? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . '@siswa.lms';
                $password = $parts[3] ?? $defaultPassword;
                $kelas = !empty($parts[4]) ? $parts[4] : null;
                $lingkungan = !empty($parts[5]) ? $parts[5] : null;
                $asalSekolah = !empty($parts[6]) ? $parts[6] : null;

                // Check existing
                if (User::where('email', $email)->exists()) {
                    $errors[] = "Baris {$lineNum}: Email '{$email}' sudah terdaftar";
                    continue;
                }
                if (User::where('name', $name)->exists()) {
                    $errors[] = "Baris {$lineNum}: Username '{$name}' sudah terdaftar";
                    continue;
                }

                User::create([
                    'name' => $name,
                    'full_name' => $fullName,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'kelas' => $kelas,
                    'lingkungan' => $lingkungan,
                    'asal_sekolah' => $asalSekolah,
                    'role' => 'student',
                ]);

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }

        $message = "{$imported} siswa berhasil diimport.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " baris error.";
        }

        return redirect()->route('admin.students.index')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    /**
     * Delete all students (reset).
     */
    public function deleteAll()
    {
        $count = User::where('role', 'student')->count();

        // Delete related records to avoid FK constraint errors
        $studentIds = User::where('role', 'student')->pluck('id');
        if ($studentIds->isNotEmpty()) {
            $sessionIds = ExamSession::whereIn('user_id', $studentIds)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                StudentAnswer::whereIn('exam_session_id', $sessionIds)->delete();
                CheatLog::whereIn('exam_session_id', $sessionIds)->delete();
                ExamSession::whereIn('id', $sessionIds)->delete();
            }
        }

        User::where('role', 'student')->delete();

        return redirect()->route('admin.students.index')
            ->with('success', "{$count} siswa berhasil dihapus.");
    }
}
