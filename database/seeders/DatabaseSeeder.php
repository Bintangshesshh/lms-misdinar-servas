<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User only
        // Students and questions will be added via Admin Panel
        
        // Idempotent seeding: safe to run multiple times without duplicate key errors.
        User::updateOrCreate(
            ['email' => 'admin@misdinar.com'],
            [
                'name' => 'admin',
                'full_name' => 'Administrator Misdinar',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        $this->command->info("==========================================");
        $this->command->info("  LMS MISDINAR - Database Ready!");
        $this->command->info("==========================================");
        $this->command->info("");
        $this->command->info("  ADMIN LOGIN:");
        $this->command->info("  Email    : admin@misdinar.com");
        $this->command->info("  Password : admin123");
        $this->command->info("");
        $this->command->info("  Silakan login ke Admin Panel untuk:");
        $this->command->info("  - Tambahkan siswa");  
        $this->command->info("  - Buat ujian dan soal");
        $this->command->info("==========================================");
    }
}