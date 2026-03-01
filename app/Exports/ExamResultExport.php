<?php

namespace App\Exports;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\StudentAnswer;
use App\Models\CheatLog;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;

class ExamResultExport
{
    protected Exam $exam;

    public function __construct(Exam $exam)
    {
        $this->exam = $exam;
    }

    private function headerStyle(): Style
    {
        return new Style(
            fontBold: true,
            fontSize: 11,
            fontColor: Color::WHITE,
            backgroundColor: '4472C4',
        );
    }

    private function titleStyle(): Style
    {
        return new Style(fontBold: true, fontSize: 14);
    }

    private function keyStyle(): Style
    {
        return new Style(fontBold: true, fontSize: 10, backgroundColor: 'E2EFDA');
    }

    private function sectionStyle(): Style
    {
        return new Style(fontBold: true, backgroundColor: 'D9E2F3');
    }

    private function terminatedStyle(): Style
    {
        return new Style(fontBold: true, backgroundColor: 'FCE4EC');
    }

    public function export(): string
    {
        $exam = $this->exam;
        $questions = $exam->questions()->orderBy('order')->get();

        /** @var \Illuminate\Support\Collection<int, \App\Models\ExamSession> $sessions */
        $sessions = ExamSession::where('exam_id', $exam->id)
            ->whereNotNull('joined_at')
            ->with(['user:id,name,email,full_name,kelas,umur,lingkungan,asal_sekolah', 'cheatLogs'])
            ->get();

        // Preload all answers keyed by session_id
        $allAnswers = StudentAnswer::whereIn('exam_session_id', $sessions->pluck('id'))
            ->get()
            ->groupBy('exam_session_id');

        // Preload all cheat logs
        $allLogs = CheatLog::whereIn('exam_session_id', $sessions->pluck('id'))
            ->orderBy('occurred_at')
            ->get();

        // Calculate terminated count per session
        // Simulate score deductions: each violation = -30, each time score hits 0 = 1 terminated
        // If reinstated, score resets to 60
        $terminatedCounts = [];
        foreach ($sessions as $session) {
            $sessionLogs = $allLogs->where('exam_session_id', $session->id);
            $score = 100;
            $terminated = 0;
            foreach ($sessionLogs as $log) {
                $score -= 30;
                if ($score <= 0) {
                    $terminated++;
                    $score = 60; // reinstate gives 60
                }
            }
            if ($session->status === 'blocked' && $terminated === 0) {
                $terminated = 1;
            }
            $terminatedCounts[$session->id] = $terminated;
        }

        $filename = 'Laporan_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam->title) . '_' . now()->format('Y-m-d_His') . '.xlsx';
        $filePath = storage_path('app/' . $filename);

        $options = new Options();
        $writer = new Writer($options);
        $writer->openToFile($filePath);

        // ============================================
        // SHEET 1: RINGKASAN SISWA
        // ============================================
        $writer->getCurrentSheet()->setName('Ringkasan');

        $writer->addRow(Row::fromValuesWithStyle(['LAPORAN UJIAN: ' . $exam->title], $this->titleStyle()));
        $writer->addRow(Row::fromValues(['Tanggal: ' . now()->format('d/m/Y H:i')]));
        $writer->addRow(Row::fromValues(['Durasi: ' . $exam->duration_minutes . ' menit']));
        $writer->addRow(Row::fromValues(['Jumlah Soal: ' . $questions->count()]));
        $writer->addRow(Row::fromValues(['Total Poin Maksimal: ' . $questions->sum('points')]));
        $writer->addRow(Row::fromValues([]));

        $headers = [
            'No', 'Nama Lengkap', 'Username', 'Email',
            'Kelas', 'Umur', 'Lingkungan', 'Asal Sekolah',
            'Status',
            'Skor Akademik', 'Skor Integritas',
            'Jawaban Benar', 'Jawaban Salah', 'Tidak Dijawab',
            'Poin Diperoleh', 'Total Pelanggaran',
            'Terminated (x)', 'Tab Switch', 'Screenshot', 'Split Screen', 'Window Blur',
            'Fullscreen Exit', 'Resize/Split',
            'Waktu Join', 'Waktu Selesai',
        ];
        $writer->addRow(Row::fromValuesWithStyle($headers, $this->headerStyle()));

        $no = 1;
        foreach ($sessions as $session) {
            $answers = $allAnswers->get($session->id, collect());

            $correctCount = $answers->filter(fn($a) => $a->is_correct)->count();
            $wrongCount = $answers->filter(fn($a) => !$a->is_correct && $a->selected_answer !== null)->count();
            $unanswered = $questions->count() - $answers->count();

            $earnedPoints = 0;
            foreach ($answers->filter(fn($a) => $a->is_correct) as $ans) {
                $q = $questions->firstWhere('id', $ans->question_id);
                if ($q) $earnedPoints += $q->points;
            }

            $cheatLogs = $session->cheatLogs;
            $tabSwitch = $cheatLogs->where('violation_type', 'tab_switch')->count();
            $screenshot = $cheatLogs->where('violation_type', 'screenshot')->count();
            $splitScreen = $cheatLogs->where('violation_type', 'split_screen')->count();
            $windowBlur = $cheatLogs->where('violation_type', 'window_blur')->count();
            $fullscreenExit = $cheatLogs->where('violation_type', 'fullscreen_exit')->count();
            $resizeSuspicion = $cheatLogs->where('violation_type', 'resize_suspicion')->count();

            $statusLabels = [
                'ongoing' => 'Sedang Mengerjakan',
                'completed' => 'Selesai',
                'blocked' => 'TERMINATED',
            ];

            $row = [
                $no++,
                $session->user->full_name ?? $session->user->name ?? '-',
                $session->user->name ?? '-',
                $session->user->email ?? '-',
                $session->user->kelas ?? '-',
                $session->user->umur ?? '-',
                $session->user->lingkungan ?? '-',
                $session->user->asal_sekolah ?? '-',
                $statusLabels[$session->status] ?? $session->status,
                $session->score_academic ?? 0,
                $session->score_integrity,
                $correctCount,
                $wrongCount,
                $unanswered,
                $earnedPoints,
                $cheatLogs->count(),
                $terminatedCounts[$session->id] ?? 0,
                $tabSwitch,
                $screenshot,
                $splitScreen,
                $windowBlur,
                $fullscreenExit,
                $resizeSuspicion,
                $session->joined_at?->format('H:i:s') ?? '-',
                $session->end_time?->format('H:i:s') ?? '-',
            ];

            if ($session->status === 'blocked') {
                $writer->addRow(Row::fromValuesWithStyle($row, $this->terminatedStyle()));
            } else {
                $writer->addRow(Row::fromValues($row));
            }
        }

        // ============================================
        // SHEET 2: DETAIL JAWABAN PER SOAL
        // ============================================
        $sheet2 = $writer->addNewSheetAndMakeItCurrent();
        $sheet2->setName('Detail Jawaban');

        $writer->addRow(Row::fromValuesWithStyle(['DETAIL JAWABAN PER SOAL'], $this->titleStyle()));
        $writer->addRow(Row::fromValues(['Ujian: ' . $exam->title]));
        $writer->addRow(Row::fromValues([]));

        $ansHeaders = ['No', 'Nama Lengkap', 'Kelas', 'Email'];
        foreach ($questions as $i => $q) {
            $ansHeaders[] = 'Soal ' . ($i + 1) . ' (' . $q->points . ' poin)';
        }
        $ansHeaders[] = 'Total Benar';
        $ansHeaders[] = 'Skor Akademik';
        $writer->addRow(Row::fromValuesWithStyle($ansHeaders, $this->headerStyle()));

        $correctRow = ['', '', '', 'KUNCI JAWABAN →'];
        foreach ($questions as $q) {
            $correctRow[] = strtoupper($q->correct_answer);
        }
        $correctRow[] = '';
        $correctRow[] = '';
        $writer->addRow(Row::fromValuesWithStyle($correctRow, $this->keyStyle()));

        $no = 1;
        foreach ($sessions as $session) {
            $answers = $allAnswers->get($session->id, collect())->keyBy('question_id');
            $row = [
                $no++,
                $session->user->full_name ?? $session->user->name ?? '-',
                $session->user->kelas ?? '-',
                $session->user->email ?? '-',
            ];

            $correct = 0;
            foreach ($questions as $q) {
                $ans = $answers->get($q->id);
                if (!$ans || !$ans->selected_answer) {
                    $row[] = '-';
                } else {
                    $letter = strtoupper($ans->selected_answer);
                    $isCorrect = $ans->is_correct;
                    $row[] = $letter . ($isCorrect ? ' ✓' : ' ✗');
                    if ($isCorrect) $correct++;
                }
            }
            $row[] = $correct;
            $row[] = $session->score_academic ?? 0;
            $writer->addRow(Row::fromValues($row));
        }

        // ============================================
        // SHEET 3: LOG PELANGGARAN
        // ============================================
        $sheet3 = $writer->addNewSheetAndMakeItCurrent();
        $sheet3->setName('Log Pelanggaran');

        $writer->addRow(Row::fromValuesWithStyle(['LOG PELANGGARAN INTEGRITAS'], $this->titleStyle()));
        $writer->addRow(Row::fromValues(['Ujian: ' . $exam->title]));
        $writer->addRow(Row::fromValues([]));

        $logHeaders = ['No', 'Nama Lengkap', 'Kelas', 'Email', 'Jenis Pelanggaran', 'Durasi (detik)', 'Waktu Kejadian'];
        $writer->addRow(Row::fromValuesWithStyle($logHeaders, $this->headerStyle()));

        $typeLabels = [
            'tab_switch' => 'Pindah Tab',
            'split_screen' => 'Split Screen / Shortcut',
            'window_blur' => 'Keluar Window',
            'screenshot' => 'Screenshot',
            'device_offline' => 'Device Offline',
        ];

        $no = 1;
        foreach ($allLogs as $log) {
            $session = $sessions->firstWhere('id', $log->exam_session_id);
            $row = [
                $no++,
                $session->user->full_name ?? $session->user->name ?? '-',
                $session->user->kelas ?? '-',
                $session->user->email ?? '-',
                $typeLabels[$log->violation_type] ?? $log->violation_type,
                $log->duration_seconds,
                $log->occurred_at,
            ];
            $writer->addRow(Row::fromValues($row));
        }

        // ============================================
        // SHEET 4: STATISTIK
        // ============================================
        $sheet4 = $writer->addNewSheetAndMakeItCurrent();
        $sheet4->setName('Statistik');

        $writer->addRow(Row::fromValuesWithStyle(['STATISTIK UJIAN'], $this->titleStyle()));
        $writer->addRow(Row::fromValues(['Ujian: ' . $exam->title]));
        $writer->addRow(Row::fromValues([]));

        $completedSessions = $sessions->where('status', 'completed');
        $blockedSessions = $sessions->where('status', 'blocked');
        $academicScores = $completedSessions->pluck('score_academic')->filter();
        $integrityScores = $sessions->pluck('score_integrity');
        $totalTerminated = array_sum($terminatedCounts);

        $writer->addRow(Row::fromValuesWithStyle(['UMUM', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValues(['Total Peserta', $sessions->count()]));
        $writer->addRow(Row::fromValues(['Selesai', $completedSessions->count()]));
        $writer->addRow(Row::fromValues(['Terminated (saat ini)', $blockedSessions->count()]));
        $writer->addRow(Row::fromValues(['Total Kali Terminated', $totalTerminated]));
        $writer->addRow(Row::fromValues(['Masih Mengerjakan', $sessions->where('status', 'ongoing')->count()]));
        $writer->addRow(Row::fromValues([]));

        // Breakdown per kelas
        /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\ExamSession>> $kelasGroups */
        $kelasGroups = $sessions->groupBy(function ($s) {
            return $s->user->kelas ?? 'Tidak Diketahui';
        });
        if ($kelasGroups->count() > 1) {
            $writer->addRow(Row::fromValuesWithStyle(['PESERTA PER KELAS', ''], $this->sectionStyle()));
            foreach ($kelasGroups as $kelas => $group) {
                $writer->addRow(Row::fromValues(['Kelas ' . $kelas, $group->count() . ' siswa']));
            }
            $writer->addRow(Row::fromValues([]));
        }

        // Breakdown per lingkungan
        /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\ExamSession>> $lingkGroups */
        $lingkGroups = $sessions->groupBy(function ($s) {
            return $s->user->lingkungan ?? 'Tidak Diketahui';
        });
        if ($lingkGroups->count() > 1) {
            $writer->addRow(Row::fromValuesWithStyle(['PESERTA PER LINGKUNGAN', ''], $this->sectionStyle()));
            foreach ($lingkGroups as $lingk => $group) {
                $writer->addRow(Row::fromValues([$lingk, $group->count() . ' siswa']));
            }
            $writer->addRow(Row::fromValues([]));
        }

        $writer->addRow(Row::fromValuesWithStyle(['SKOR AKADEMIK', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValues(['Rata-rata', $academicScores->count() > 0 ? round($academicScores->avg(), 1) : '-']));
        $writer->addRow(Row::fromValues(['Tertinggi', $academicScores->count() > 0 ? $academicScores->max() : '-']));
        $writer->addRow(Row::fromValues(['Terendah', $academicScores->count() > 0 ? $academicScores->min() : '-']));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValuesWithStyle(['SKOR INTEGRITAS', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValues(['Rata-rata', round($integrityScores->avg(), 1)]));
        $writer->addRow(Row::fromValues(['Tertinggi', $integrityScores->max()]));
        $writer->addRow(Row::fromValues(['Terendah', $integrityScores->min()]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValuesWithStyle(['PELANGGARAN', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValues(['Total Pelanggaran', $allLogs->count()]));
        $writer->addRow(Row::fromValues(['Pindah Tab', $allLogs->where('violation_type', 'tab_switch')->count()]));
        $writer->addRow(Row::fromValues(['Screenshot', $allLogs->where('violation_type', 'screenshot')->count()]));
        $writer->addRow(Row::fromValues(['Split Screen / Shortcut', $allLogs->where('violation_type', 'split_screen')->count()]));
        $writer->addRow(Row::fromValues(['Keluar Window', $allLogs->where('violation_type', 'window_blur')->count()]));
        $writer->addRow(Row::fromValues([]));

        // Per-question accuracy
        $writer->addRow(Row::fromValuesWithStyle(['AKURASI PER SOAL', '', '', '', '', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValuesWithStyle(['Soal', 'Pertanyaan', 'Kunci', 'Benar', 'Salah', '% Akurasi'], $this->headerStyle()));

        foreach ($questions as $i => $q) {
            $qAnswers = StudentAnswer::where('question_id', $q->id)
                ->whereIn('exam_session_id', $sessions->pluck('id'))
                ->get();

            $totalAnswered = $qAnswers->whereNotNull('selected_answer')->count();
            $correctCount = $qAnswers->filter(fn($a) => $a->is_correct)->count();
            $wrongCount = $totalAnswered - $correctCount;
            $accuracy = $totalAnswered > 0 ? round(($correctCount / $totalAnswered) * 100, 1) : 0;

            $writer->addRow(Row::fromValues([
                'Soal ' . ($i + 1),
                mb_substr($q->question_text, 0, 80),
                strtoupper($q->correct_answer),
                $correctCount,
                $wrongCount,
                $accuracy . '%',
            ]));
        }

        $writer->close();

        return $filePath;
    }
}
