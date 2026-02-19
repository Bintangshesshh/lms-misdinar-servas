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
            'name' => 'Admin Misdinar',
            'full_name' => 'Administrator Misdinar',
            'email' => 'admin@misdinar.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Student User
        $student = User::create([
            'name' => 'peserta_001',
            'full_name' => 'Yohanes Pratama',
            'kelas' => 'SMP Kelas 8',
            'umur' => 14,
            'lingkungan' => 'Paroki Santo Paulus',
            'email' => 'yohanes@email.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        // 3. Extra students for testing monitoring
        $student2 = User::create([
            'name' => 'peserta_002',
            'full_name' => 'Maria Angelica',
            'kelas' => 'SMP Kelas 8',
            'umur' => 13,
            'lingkungan' => 'Paroki Santo Yohanes',
            'email' => 'maria@email.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        $student3 = User::create([
            'name' => 'peserta_003',
            'full_name' => 'Petrus Setiawan',
            'kelas' => 'SMP Kelas 9',
            'umur' => 15,
            'lingkungan' => 'Paroki Santo Petrus',
            'email' => 'petrus@email.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        // 4. API Token for testing
        $token = $student->createToken('test-token')->plainTextToken;

        // 5. Sample Exam
        $exam1 = Exam::create([
            'title' => 'Ujian Pengetahuan Liturgi',
            'mata_pelajaran' => 'Liturgi',
            'duration_minutes' => 45,
            'is_active' => true,
            'status' => 'draft',
        ]);

        $exam2 = Exam::create([
            'title' => 'Ujian Pengetahuan Kitab Suci',
            'mata_pelajaran' => 'Kitab Suci',
            'duration_minutes' => 30,
            'is_active' => true,
            'status' => 'draft',
        ]);

        // 6. Seed 5 sample questions for Liturgi exam
        $questions = [
            [
                'question_text' => 'Apa warna jubah imam pada masa Adven?',
                'option_a' => 'Putih',
                'option_b' => 'Ungu',
                'option_c' => 'Hijau',
                'option_d' => 'Merah',
                'correct_answer' => 'b',
                'points' => 10,
                'order' => 1,
            ],
            [
                'question_text' => 'Apa nama peralatan liturgi yang digunakan untuk membawa hosti?',
                'option_a' => 'Piala',
                'option_b' => 'Patena',
                'option_c' => 'Monsrans',
                'option_d' => 'Ciborium',
                'correct_answer' => 'd',
                'points' => 10,
                'order' => 2,
            ],
            [
                'question_text' => 'Berapa kali Misdinar melakukan tanda salib kecil saat Injil dibacakan?',
                'option_a' => '1 kali',
                'option_b' => '2 kali',
                'option_c' => '3 kali',
                'option_d' => '4 kali',
                'correct_answer' => 'c',
                'points' => 15,
                'order' => 3,
            ],
            [
                'question_text' => 'Apa yang harus dilakukan Misdinar saat membawa lilin dalam prosesi?',
                'option_a' => 'Berjalan cepat',
                'option_b' => 'Berjalan sambil berbicara',
                'option_c' => 'Berjalan dengan khidmat dan hati-hati',
                'option_d' => 'Berjalan sambil menunduk',
                'correct_answer' => 'c',
                'points' => 15,
                'order' => 4,
            ],
            [
                'question_text' => 'Pada saat konsekrasi, Misdinar harus:',
                'option_a' => 'Berdiri tegak',
                'option_b' => 'Berlutut dengan khidmat',
                'option_c' => 'Duduk tenang',
                'option_d' => 'Berdiri sambil menunduk',
                'correct_answer' => 'b',
                'points' => 10,
                'order' => 5,
            ],
        ];

        foreach ($questions as $q) {
            $exam1->questions()->create($q);
        }

        $this->command->info("-----------------------------------------");
        $this->command->info("ADMIN     : admin@misdinar.com / password");
        $this->command->info("PESERTA 1 : yohanes@email.com / password");
        $this->command->info("PESERTA 2 : maria@email.com    / password");
        $this->command->info("PESERTA 3 : petrus@email.com   / password");
        $this->command->info("API TOKEN : {$token}");
        $this->command->info("UJIAN     : '{$exam1->title}' => {$exam1->questions()->count()} soal");
        $this->command->info("-----------------------------------------");
    }
}