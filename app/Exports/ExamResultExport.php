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
        return (new Style())
            ->setFontBold()
            ->setFontSize(11)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('4472C4');
    }

    private function titleStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontSize(14);
    }

    private function keyStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontSize(10)
            ->setBackgroundColor('E2EFDA');
    }

    private function sectionStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setBackgroundColor('D9E2F3');
    }

    private function terminatedStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setBackgroundColor('FCE4EC');
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

        // Use actual violation_count from session (set by IntegrityController)
        $terminatedCounts = [];
        foreach ($sessions as $session) {
            $terminatedCounts[$session->id] = $session->violation_count ?? 0;
        }

        // Canonical scoring snapshot used by all sheets so Excel stays consistent.
        $questionsById = $questions->keyBy('id');
        $totalQuestions = (int) $questions->count();
        $totalPoints = (int) ($questions->sum('points') ?: $questions->count());

        $questionStats = [];
        foreach ($questions as $q) {
            $questionStats[$q->id] = [
                'answered' => 0,
                'correct' => 0,
                'wrong' => 0,
                'essay_answered' => 0,
            ];
        }

        $sessionMetrics = [];
        foreach ($sessions as $session) {
            $answers = $allAnswers->get($session->id, collect());

            $correctCount = 0;
            $wrongCount = 0;
            $essayCount = 0;
            $answeredCount = 0;
            $earnedPoints = 0;

            foreach ($answers as $ans) {
                $q = $questionsById->get($ans->question_id);
                if (!$q) {
                    continue;
                }

                if ($q->question_type === 'essay') {
                    $essayText = trim((string) ($ans->answer_text ?? ''));
                    if ($essayText !== '') {
                        $essayCount++;
                        $answeredCount++;
                        $questionStats[$q->id]['essay_answered']++;
                    }
                    continue;
                }

                $selected = strtolower(trim((string) ($ans->selected_answer ?? '')));
                if ($selected === '') {
                    continue;
                }

                $answeredCount++;
                $questionStats[$q->id]['answered']++;

                $correct = strtolower(trim((string) ($q->correct_answer ?? '')));
                $isCorrect = $selected === $correct;

                if ($isCorrect) {
                    $correctCount++;
                    $earnedPoints += (int) ($q->points ?: 1);
                    $questionStats[$q->id]['correct']++;
                } else {
                    $wrongCount++;
                    $questionStats[$q->id]['wrong']++;
                }
            }

            $unanswered = max(0, $totalQuestions - $answeredCount);
            $academicScore = $session->score_academic;
            if ($academicScore === null || ((float) $academicScore) <= 0.0 && $earnedPoints > 0) {
                $academicScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;
            }

            $sessionMetrics[$session->id] = [
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'essay_count' => $essayCount,
                'answered_count' => $answeredCount,
                'unanswered_count' => $unanswered,
                'earned_points' => $earnedPoints,
                'academic_score' => (float) $academicScore,
            ];
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

        $writer->addRow(Row::fromValues(['LAPORAN UJIAN: ' . $exam->title], $this->titleStyle()));
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
            'Jawaban Benar', 'Jawaban Salah', 'Soal Essay', 'Tidak Dijawab',
            'Poin Diperoleh', 'Jumlah Pelanggaran', 'Total Log Pelanggaran',
            'Tab Switch', 'Screenshot', 'Split Screen', 'Window Blur',
            'Fullscreen Exit', 'Resize/Split',
            'Waktu Join', 'Waktu Selesai',
        ];
        $writer->addRow(Row::fromValues($headers, $this->headerStyle()));

        $no = 1;
        foreach ($sessions as $session) {
            $metrics = $sessionMetrics[$session->id] ?? [
                'correct_count' => 0,
                'wrong_count' => 0,
                'essay_count' => 0,
                'unanswered_count' => $totalQuestions,
                'earned_points' => 0,
                'academic_score' => 0,
            ];

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
                $metrics['academic_score'],
                $session->score_integrity,
                $metrics['correct_count'],
                $metrics['wrong_count'],
                $metrics['essay_count'],
                $metrics['unanswered_count'],
                $metrics['earned_points'],
                $session->violation_count ?? 0,
                $cheatLogs->count(),
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
                $writer->addRow(Row::fromValues($row, $this->terminatedStyle()));
            } else {
                $writer->addRow(Row::fromValues($row));
            }
        }

        // ============================================
        // SHEET 2: DETAIL JAWABAN PER SOAL
        // ============================================
        $sheet2 = $writer->addNewSheetAndMakeItCurrent();
        $sheet2->setName('Detail Jawaban');

        $writer->addRow(Row::fromValues(['DETAIL JAWABAN PER SOAL'], $this->titleStyle()));
        $writer->addRow(Row::fromValues(['Ujian: ' . $exam->title]));
        $writer->addRow(Row::fromValues([]));

        $ansHeaders = ['No', 'Nama Lengkap', 'Kelas', 'Email'];
        foreach ($questions as $i => $q) {
            $ansHeaders[] = 'Soal ' . ($i + 1) . ' (' . $q->points . ' poin)';
        }
        $ansHeaders[] = 'Total Benar';
        $ansHeaders[] = 'Skor Akademik';
        $writer->addRow(Row::fromValues($ansHeaders, $this->headerStyle()));

        $correctRow = ['', '', '', 'KUNCI JAWABAN →'];
        foreach ($questions as $q) {
            if ($q->question_type === 'essay') {
                $correctRow[] = '(Essay)';
            } else {
                $correctRow[] = strtoupper($q->correct_answer);
            }
        }
        $correctRow[] = '';
        $correctRow[] = '';
        $writer->addRow(Row::fromValues($correctRow, $this->keyStyle()));

        $no = 1;
        foreach ($sessions as $session) {
            $answers = $allAnswers->get($session->id, collect())->keyBy('question_id');
            $metrics = $sessionMetrics[$session->id] ?? ['correct_count' => 0, 'academic_score' => 0];
            $row = [
                $no++,
                $session->user->full_name ?? $session->user->name ?? '-',
                $session->user->kelas ?? '-',
                $session->user->email ?? '-',
            ];

            foreach ($questions as $q) {
                $ans = $answers->get($q->id);
                if ($q->question_type === 'essay') {
                    // Essay: show the actual text (truncated for sheet 2)
                    if ($ans && $ans->answer_text) {
                        $row[] = mb_substr($ans->answer_text, 0, 100);
                    } else {
                        $row[] = '-';
                    }
                } else {
                    // MC: show letter + check mark
                    if (!$ans || !$ans->selected_answer) {
                        $row[] = '-';
                    } else {
                        $letter = strtoupper((string) $ans->selected_answer);
                        $selected = strtolower(trim((string) $ans->selected_answer));
                        $correctAnswer = strtolower(trim((string) ($q->correct_answer ?? '')));
                        $isCorrect = $selected !== '' && $selected === $correctAnswer;
                        $row[] = $letter . ($isCorrect ? ' ✓' : ' ✗');
                    }
                }
            }
            $row[] = $metrics['correct_count'];
            $row[] = $metrics['academic_score'];
            $writer->addRow(Row::fromValues($row));
        }

        // ============================================
        // SHEET 3: LOG PELANGGARAN
        // ============================================
        $sheet3 = $writer->addNewSheetAndMakeItCurrent();
        $sheet3->setName('Log Pelanggaran');

        $writer->addRow(Row::fromValues(['LOG PELANGGARAN INTEGRITAS'], $this->titleStyle()));
        $writer->addRow(Row::fromValues(['Ujian: ' . $exam->title]));
        $writer->addRow(Row::fromValues([]));

        $logHeaders = ['No', 'Nama Lengkap', 'Kelas', 'Email', 'Jenis Pelanggaran', 'Durasi (detik)', 'Waktu Kejadian'];
        $writer->addRow(Row::fromValues($logHeaders, $this->headerStyle()));

        $typeLabels = [
            'tab_switch' => 'Pindah Tab',
            'split_screen' => 'Split Screen / Shortcut',
            'window_blur' => 'Keluar Window',
            'screenshot' => 'Screenshot',
            'device_offline' => 'Device Offline',
            'fullscreen_exit' => 'Keluar Fullscreen',
            'resize_suspicion' => 'Resize / Split Suspicion',
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

        $writer->addRow(Row::fromValues(['STATISTIK UJIAN'], $this->titleStyle()));
        $writer->addRow(Row::fromValues(['Ujian: ' . $exam->title]));
        $writer->addRow(Row::fromValues([]));

        $completedSessions = $sessions->where('status', 'completed');
        $blockedSessions = $sessions->where('status', 'blocked');
        $academicScores = $completedSessions->map(function ($session) use ($sessionMetrics) {
            return (float) ($sessionMetrics[$session->id]['academic_score'] ?? 0);
        });
        $integrityScores = $sessions->pluck('score_integrity');
        $totalTerminated = array_sum($terminatedCounts);

        $writer->addRow(Row::fromValues(['UMUM', ''], $this->sectionStyle()));
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
            $writer->addRow(Row::fromValues(['PESERTA PER KELAS', ''], $this->sectionStyle()));
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
            $writer->addRow(Row::fromValues(['PESERTA PER LINGKUNGAN', ''], $this->sectionStyle()));
            foreach ($lingkGroups as $lingk => $group) {
                $writer->addRow(Row::fromValues([$lingk, $group->count() . ' siswa']));
            }
            $writer->addRow(Row::fromValues([]));
        }

        $writer->addRow(Row::fromValues(['SKOR AKADEMIK', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValues(['Rata-rata', $academicScores->count() > 0 ? round($academicScores->avg(), 1) : '-']));
        $writer->addRow(Row::fromValues(['Tertinggi', $academicScores->count() > 0 ? $academicScores->max() : '-']));
        $writer->addRow(Row::fromValues(['Terendah', $academicScores->count() > 0 ? $academicScores->min() : '-']));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['SKOR INTEGRITAS', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValues(['Rata-rata', round($integrityScores->avg(), 1)]));
        $writer->addRow(Row::fromValues(['Tertinggi', $integrityScores->max()]));
        $writer->addRow(Row::fromValues(['Terendah', $integrityScores->min()]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['PELANGGARAN', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValues(['Total Pelanggaran', $allLogs->count()]));
        $writer->addRow(Row::fromValues(['Pindah Tab', $allLogs->where('violation_type', 'tab_switch')->count()]));
        $writer->addRow(Row::fromValues(['Screenshot', $allLogs->where('violation_type', 'screenshot')->count()]));
        $writer->addRow(Row::fromValues(['Split Screen / Shortcut', $allLogs->where('violation_type', 'split_screen')->count()]));
        $writer->addRow(Row::fromValues(['Keluar Window', $allLogs->where('violation_type', 'window_blur')->count()]));
        $writer->addRow(Row::fromValues([]));

        // Per-question accuracy
        $writer->addRow(Row::fromValues(['AKURASI PER SOAL', '', '', '', '', ''], $this->sectionStyle()));
        $writer->addRow(Row::fromValues(['Soal', 'Pertanyaan', 'Kunci', 'Benar', 'Salah', '% Akurasi'], $this->headerStyle()));

        foreach ($questions as $i => $q) {
            $qStat = $questionStats[$q->id] ?? [
                'answered' => 0,
                'correct' => 0,
                'wrong' => 0,
                'essay_answered' => 0,
            ];

            $isEssay = $q->question_type === 'essay';
            if ($isEssay) {
                $totalAnswered = $qStat['essay_answered'];
                $writer->addRow(Row::fromValues([
                    'Soal ' . ($i + 1) . ' (Essay)',
                    mb_substr($q->question_text, 0, 80),
                    '(Essay)',
                    '-',
                    '-',
                    $totalAnswered . ' jawaban',
                ]));
            } else {
                $totalAnswered = $qStat['answered'];
                $correctCount = $qStat['correct'];
                $wrongCount = $qStat['wrong'];
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
        }

        // ============================================
        // SHEET 5: JAWABAN RESPONDEN (Google Forms Style)
        // Full raw answers — one row per student, columns = questions
        // ============================================
        $sheet5 = $writer->addNewSheetAndMakeItCurrent();
        $sheet5->setName('Jawaban Responden');

        $writer->addRow(Row::fromValues(['JAWABAN RESPONDEN (RAW)'], $this->titleStyle()));
        $writer->addRow(Row::fromValues(['Ujian: ' . $exam->title]));
        $writer->addRow(Row::fromValues(['Format: Seperti Google Forms — jawaban asli setiap responden']));
        $writer->addRow(Row::fromValues([]));

        // Headers: Nama, Kelas, Email, then each question text
        $rawHeaders = ['Nama Lengkap', 'Kelas', 'Email', 'Status', 'Skor', 'Pelanggaran'];
        foreach ($questions as $i => $q) {
            $rawHeaders[] = 'Soal ' . ($i + 1) . ': ' . mb_substr($q->question_text, 0, 60);
        }
        $writer->addRow(Row::fromValues($rawHeaders, $this->headerStyle()));

        // Question type row
        $typeRow = ['', '', '', '', '', ''];
        foreach ($questions as $q) {
            $typeRow[] = $q->question_type === 'essay' ? '[Essay]' : '[Pilihan Ganda]';
        }
        $writer->addRow(Row::fromValues($typeRow, $this->keyStyle()));

        foreach ($sessions as $session) {
            $answers = $allAnswers->get($session->id, collect())->keyBy('question_id');

            $statusLabels = [
                'ongoing' => 'Mengerjakan',
                'completed' => 'Selesai',
                'blocked' => 'TERMINATED',
            ];

            $row = [
                $session->user->full_name ?? $session->user->name ?? '-',
                $session->user->kelas ?? '-',
                $session->user->email ?? '-',
                $statusLabels[$session->status] ?? $session->status,
                $sessionMetrics[$session->id]['academic_score'] ?? 0,
                $session->violation_count ?? 0,
            ];

            foreach ($questions as $q) {
                $ans = $answers->get($q->id);
                if (!$ans) {
                    $row[] = '(tidak dijawab)';
                } elseif ($q->question_type === 'essay') {
                    // Essay: show full text
                    $row[] = $ans->answer_text ?? '(tidak dijawab)';
                } else {
                    // MC: show the actual option text they chose
                    $selected = $ans->selected_answer;
                    if (!$selected) {
                        $row[] = '(tidak dijawab)';
                    } else {
                        $optionField = 'option_' . strtolower($selected);
                        $optionText = $q->{$optionField} ?? strtoupper($selected);
                        $row[] = strtoupper($selected) . '. ' . $optionText;
                    }
                }
            }

            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $filePath;
    }
}
