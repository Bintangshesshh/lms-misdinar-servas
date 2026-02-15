<?php

namespace Database\Seeders;

use App\Models\User;
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
            'email' => 'admin@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Student User
        $student = User::create([
            'name' => 'Budi Hacker',
            'email' => 'budi@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        // 3. API Token for testing
        $token = $student->createToken('test-token')->plainTextToken;

        // 4. Sample Exams (status: draft — admin needs to open lobby)
        DB::table('exams')->insert([
            'title' => 'Ujian Matematika Dasar',
            'duration_minutes' => 60,
            'is_active' => true,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('exams')->insert([
            'title' => 'Ujian Bahasa Indonesia',
            'duration_minutes' => 45,
            'is_active' => true,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("-----------------------------------------");
        $this->command->info("ADMIN: admin@sekolah.com / password");
        $this->command->info("SISWA: budi@sekolah.com / password");
        $this->command->info("API TOKEN: {$token}");
        $this->command->info("-----------------------------------------");
    }
}