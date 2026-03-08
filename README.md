# LMS-Misdinar

Learning Management System untuk Ujian Misdinar (Altar Server). Aplikasi ini dilengkapi dengan fitur anti-cheat untuk menjaga integritas ujian.

## Fitur Utama

- **Dashboard Admin**: Kelola ujian, soal, dan pantau peserta secara real-time
- **Dashboard Student**: Join lobby ujian, kerjakan soal, dan lihat hasil
- **Anti-Cheat System**: Deteksi tab switching, split screen, window blur dengan scoring integritas
- **Real-time Monitoring**: Admin dapat melihat progress siswa secara langsung
- **Export Hasil**: Download hasil ujian dalam format Excel

## Instalasi

```bash
# Clone repository
git clone https://github.com/JongBatak/LMS-Misdinar.git
cd LMS-Misdinar

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Build assets
npm run build

# Start server
php artisan serve
```

## Login Credentials (Setelah Seeding)

| Role    | Email               | Password  |
|---------|---------------------|-----------|
| Admin   | admin@misdinar.com  | admin123  |

> Siswa ditambahkan melalui Admin Panel > Kelola Siswa > Import Siswa

## Deployment 100+ Siswa

Untuk deploy online production (Hostinger/domain publik), ikuti:

- **[HOSTINGER-DEPLOYMENT.md](HOSTINGER-DEPLOYMENT.md)**
- Template env production: **[.env.hostinger.example](.env.hostinger.example)**

## Struktur Aplikasi

```
app/
├── Http/Controllers/
│   ├── AdminDashboardController.php   # Admin exam management
│   ├── StudentDashboardController.php # Student dashboard
│   ├── ExamLobbyController.php        # Exam taking flow
│   └── Api/IntegrityController.php    # Anti-cheat API
├── Models/
│   ├── User.php          # User with role (admin/student)
│   ├── Exam.php          # Exam entity
│   ├── Question.php      # Questions for exams
│   ├── ExamSession.php   # Student exam sessions
│   ├── StudentAnswer.php # Student answers
│   └── CheatLog.php      # Violation logs
└── Exports/
    └── ExamResultExport.php # Excel export
```

## API Endpoints

### Authentication Required (Sanctum)
- `POST /api/exam/{exam_id}/start` - Start exam session
- `POST /api/exam/submit-answer` - Submit answer
- `POST /api/exam/finish` - Finish exam
- `POST /api/integrity/log-violation` - Log integrity violation
- `GET /api/integrity/status/{session_id}` - Get integrity status

## Teknologi

- Laravel 11
- Blade + Tailwind CSS
- Sanctum (API Authentication)
- OpenSpout (Excel Export)

## License

MIT License
