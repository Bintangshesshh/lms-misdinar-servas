<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Facades\Hash;

class TestExamSeeder extends Seeder
{
    /**
     * Seed test data for student testing (10-20 students + 1 test exam)
     * 
     * Run: php artisan db:seed --class=TestExamSeeder
     */
    public function run(): void
    {
        echo "🚀 Creating test data for student testing...\n\n";

        // =========================================
        // 1. CREATE TEST STUDENTS (10 accounts)
        // =========================================
        echo "👨‍🎓 Creating 10 test students...\n";
        
        $studentNames = [
            'Andreas Putra',
            'Benediktus Wijaya', 
            'Christina Dewi',
            'Dominikus Santoso',
            'Elisabeth Putri',
            'Fransiskus Gunawan',
            'Gabriel Setiawan',
            'Helena Kusuma',
            'Ignatius Prasetyo',
            'Yohanes Budi',
        ];

        $students = [];
        foreach ($studentNames as $index => $name) {
            $number = $index + 1;
            $email = 'siswa' . $number . '@test.com';
            $password = 'password123'; // Simple password untuk testing
            
            $student = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'role' => 'student',
                ]
            );
            
            $students[] = $student;
            echo "  ✅ {$name} ({$email}) - Password: {$password}\n";
        }

        echo "\n✅ 10 test students created!\n\n";

        // =========================================
        // 2. CREATE TEST EXAM (15 minutes, 10 questions)
        // =========================================
        echo "📝 Creating test exam...\n";

        $exam = Exam::firstOrCreate(
            ['title' => 'Ujian Testing - Pengetahuan Umum'],
            [
                'mata_pelajaran' => 'Pengetahuan Umum',
                'duration_minutes' => 15, // 15 menit cukup untuk testing
                'is_active' => true,
                'status' => 'draft',
                'show_answers' => true,
                'show_score_to_student' => true,
            ]
        );

        echo "  ✅ Exam created: {$exam->title}\n";
        echo "  ⏱️  Duration: {$exam->duration_minutes} minutes\n";

        // =========================================
        // 3. CREATE 10 QUESTIONS (Easy to answer)
        // =========================================
        echo "\n❓ Creating 10 test questions...\n";

        $questions = [
            [
                'question_text' => 'Berapa hasil dari 2 + 2?',
                'option_a' => '3',
                'option_b' => '4',
                'option_c' => '5',
                'option_d' => '6',
                'correct_answer' => 'b',
                'points' => 10,
            ],
            [
                'question_text' => 'Apa ibu kota Indonesia?',
                'option_a' => 'Surabaya',
                'option_b' => 'Bandung',
                'option_c' => 'Jakarta',
                'option_d' => 'Medan',
                'correct_answer' => 'c',
                'points' => 10,
            ],
            [
                'question_text' => 'Warna langit pada siang hari?',
                'option_a' => 'Merah',
                'option_b' => 'Hijau',
                'option_c' => 'Biru',
                'option_d' => 'Kuning',
                'correct_answer' => 'c',
                'points' => 10,
            ],
            [
                'question_text' => 'Berapa jumlah hari dalam seminggu?',
                'option_a' => '5',
                'option_b' => '6',
                'option_c' => '7',
                'option_d' => '8',
                'correct_answer' => 'c',
                'points' => 10,
            ],
            [
                'question_text' => 'Hewan yang bisa terbang?',
                'option_a' => 'Ikan',
                'option_b' => 'Burung',
                'option_c' => 'Kuda',
                'option_d' => 'Kucing',
                'correct_answer' => 'b',
                'points' => 10,
            ],
            [
                'question_text' => 'Berapa hasil dari 5 x 3?',
                'option_a' => '12',
                'option_b' => '13',
                'option_c' => '14',
                'option_d' => '15',
                'correct_answer' => 'd',
                'points' => 10,
            ],
            [
                'question_text' => 'Planet terdekat dengan matahari?',
                'option_a' => 'Bumi',
                'option_b' => 'Mars',
                'option_c' => 'Merkurius',
                'option_d' => 'Venus',
                'correct_answer' => 'c',
                'points' => 10,
            ],
            [
                'question_text' => 'Berapa jumlah bulan dalam setahun?',
                'option_a' => '10',
                'option_b' => '11',
                'option_c' => '12',
                'option_d' => '13',
                'correct_answer' => 'c',
                'points' => 10,
            ],
            [
                'question_text' => 'Warna darah manusia?',
                'option_a' => 'Biru',
                'option_b' => 'Merah',
                'option_c' => 'Hijau',
                'option_d' => 'Kuning',
                'correct_answer' => 'b',
                'points' => 10,
            ],
            [
                'question_text' => 'Berapa hasil dari 10 - 3?',
                'option_a' => '6',
                'option_b' => '7',
                'option_c' => '8',
                'option_d' => '9',
                'correct_answer' => 'b',
                'points' => 10,
            ],
        ];

        foreach ($questions as $index => $questionData) {
            $questionData['exam_id'] = $exam->id;
            $questionData['order'] = $index + 1;

            Question::firstOrCreate(
                [
                    'exam_id' => $exam->id,
                    'order' => $index + 1,
                ],
                $questionData
            );

            echo "  ✅ Question " . ($index + 1) . ": {$questionData['question_text']}\n";
        }

        echo "\n✅ 10 questions created!\n\n";

        // =========================================
        // 4. SUMMARY & INSTRUCTIONS
        // =========================================
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                 🎉 TEST DATA CREATED SUCCESSFULLY!           ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";

        echo "📋 SUMMARY:\n";
        echo "  • 10 Test Students created\n";
        echo "  • 1 Test Exam created (15 minutes)\n";
        echo "  • 10 Questions added (100 points total)\n\n";

        echo "👨‍💼 ADMIN LOGIN:\n";
        echo "  Email: admin@test.com\n";
        echo "  Password: password123\n\n";

        echo "👨‍🎓 STUDENT LOGINS (All passwords: password123):\n";
        echo "  ┌────────────────────────────────────────┐\n";
        foreach ($students as $index => $student) {
            $number = $index + 1;
            echo "  │ {$number}. siswa{$number}@test.com - {$student->name}\n";
        }
        echo "  └────────────────────────────────────────┘\n\n";

        echo "📝 NEXT STEPS:\n";
        echo "  1. Login as admin (admin@test.com)\n";
        echo "  2. Go to Admin Dashboard\n";
        echo "  3. Open exam: '{$exam->title}'\n";
        echo "  4. Click 'Buka Lobby' (Open Lobby)\n";
        echo "  5. Ask students to login and join\n";
        echo "  6. Wait for all 10 students to join\n";
        echo "  7. Click 'Mulai Ujian' (Start Exam)\n";
        echo "  8. Monitor students in real-time\n";
        echo "  9. After 15 minutes, exam auto-submits\n";
        echo "  10. Export results to Excel\n\n";

        echo "⚠️  IMPORTANT:\n";
        echo "  • All passwords are: password123\n";
        echo "  • Exam duration: 15 minutes (enough for testing)\n";
        echo "  • Questions are intentionally easy (for quick testing)\n";
        echo "  • You can test with fewer than 10 students\n\n";

        echo "🧪 TEST SCENARIOS:\n";
        echo "  1. Normal Flow: Login → Join → Answer → Submit\n";
        echo "  2. Anti-Cheat: Switch tabs during exam (integrity drops)\n";
        echo "  3. Admin Monitor: Real-time student tracking\n";
        echo "  4. Auto-Submit: Let timer reach 00:00\n";
        echo "  5. Excel Export: Download results after all complete\n\n";

        echo "✅ Ready for testing! Good luck! 🚀\n\n";
    }
}
