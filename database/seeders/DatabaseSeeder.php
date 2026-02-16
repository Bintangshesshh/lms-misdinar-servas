<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = User::create([
            'name' => 'Admin Guru',
            'full_name' => 'Administrator Guru',
            'email' => 'admin@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Student User
        $student = User::create([
            'name' => 'Budi Hacker',
            'full_name' => 'Muhammad Budi Santoso',
            'kelas' => '9A',
            'umur' => 15,
            'lingkungan' => 'Lingk. 3',
            'email' => 'budi@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        // 3. Extra students for testing monitoring
        $student2 = User::create([
            'name' => 'Siti Rajin',
            'full_name' => 'Siti Nurhaliza Rajin',
            'kelas' => '9A',
            'umur' => 14,
            'lingkungan' => 'Lingk. 1',
            'email' => 'siti@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        $student3 = User::create([
            'name' => 'Andi Curang',
            'full_name' => 'Andi Pratama Curang',
            'kelas' => '9B',
            'umur' => 15,
            'lingkungan' => 'Lingk. 2',
            'email' => 'andi@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        // 4. API Token for testing
        $token = $student->createToken('test-token')->plainTextToken;

        // 5. Sample Exam
        $exam1 = Exam::create([
            'title' => 'Ujian Matematika Dasar',
            'mata_pelajaran' => 'Matematika',
            'duration_minutes' => 60,
            'is_active' => true,
            'status' => 'draft',
        ]);

        $exam2 = Exam::create([
            'title' => 'Ujian Bahasa Indonesia',
            'mata_pelajaran' => 'Bahasa Indonesia',
            'duration_minutes' => 45,
            'is_active' => true,
            'status' => 'draft',
        ]);

        // 6. Seed 5 random questions for Matematika exam
        $questions = [
            [
                'question_text' => 'Berapakah hasil dari 15 × 8?',
                'option_a' => '110',
                'option_b' => '120',
                'option_c' => '130',
                'option_d' => '140',
                'correct_answer' => 'b',
                'points' => 10,
                'order' => 1,
            ],
            [
                'question_text' => 'Jika x + 5 = 12, berapakah nilai x?',
                'option_a' => '5',
                'option_b' => '6',
                'option_c' => '7',
                'option_d' => '8',
                'correct_answer' => 'c',
                'points' => 10,
                'order' => 2,
            ],
            [
                'question_text' => 'Berapakah luas persegi dengan sisi 9 cm?',
                'option_a' => '18 cm²',
                'option_b' => '36 cm²',
                'option_c' => '72 cm²',
                'option_d' => '81 cm²',
                'correct_answer' => 'd',
                'points' => 15,
                'order' => 3,
            ],
            [
                'question_text' => 'Hasil dari √144 adalah...',
                'option_a' => '10',
                'option_b' => '11',
                'option_c' => '12',
                'option_d' => '14',
                'correct_answer' => 'c',
                'points' => 15,
                'order' => 4,
            ],
            [
                'question_text' => '25% dari 200 adalah...',
                'option_a' => '25',
                'option_b' => '40',
                'option_c' => '50',
                'option_d' => '75',
                'correct_answer' => 'c',
                'points' => 10,
                'order' => 5,
            ],
        ];

        foreach ($questions as $q) {
            $exam1->questions()->create($q);
        }

        $this->command->info("-----------------------------------------");
        $this->command->info("ADMIN  : admin@sekolah.com / password");
        $this->command->info("SISWA 1: budi@sekolah.com  / password");
        $this->command->info("SISWA 2: siti@sekolah.com  / password");
        $this->command->info("SISWA 3: andi@sekolah.com  / password");
        $this->command->info("API TOKEN: {$token}");
        $this->command->info("EXAM '{$exam1->title}' => {$exam1->questions()->count()} soal seeded");
        $this->command->info("-----------------------------------------");
    }
}