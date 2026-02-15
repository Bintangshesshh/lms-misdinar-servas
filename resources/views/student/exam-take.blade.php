@extends('layouts.app-custom')
@section('title', 'Ujian - ' . $exam->title)

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Integrity Bar --}}
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Skor Integritas</span>
            <span id="integrity-score" class="text-sm font-bold text-green-600">{{ $session->score_integrity }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div id="integrity-bar" class="bg-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ $session->score_integrity }}%;"></div>
        </div>
    </div>

    {{-- Warning Banner (hidden by default) --}}
    <div id="cheat-warning" class="hidden mb-6 bg-red-50 border-2 border-red-300 rounded-xl p-4 animate-pulse">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🚨</span>
            <div>
                <p class="font-bold text-red-800">PELANGGARAN TERDETEKSI!</p>
                <p class="text-sm text-red-600" id="warning-text">Anda terdeteksi meninggalkan halaman ujian.</p>
            </div>
        </div>
    </div>

    {{-- Exam Content --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $exam->title }}</h1>
        <p class="text-gray-500 mb-8">Waktu: {{ $exam->duration_minutes }} menit</p>

        {{-- Placeholder for exam questions --}}
        <div class="space-y-6">
            <div class="p-6 bg-gray-50 rounded-lg">
                <p class="text-gray-600">
                    📝 Konten soal ujian akan dimuat di sini.<br>
                    Implementasi soal & jawaban disesuaikan kebutuhan Anda.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // ============================================
    // ANTI-CHEAT DETECTION SYSTEM
    // ============================================
    const SESSION_ID = @json($session->id);
    const LOG_URL = '/api/integrity/log-violation';
    const CSRF_TOKEN = '{{ csrf_token() }}';

    let currentIntegrity = @json($session->score_integrity);
    let blurStart = null;
    let warningTimeout = null;

    async function reportViolation(type, duration = 0) {
        try {
            const res = await fetch(LOG_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    session_id: SESSION_ID,
                    type: type,
                    duration: Math.round(duration)
                })
            });

            if (res.ok) {
                const data = await res.json();
                updateIntegrityUI(data.current_integrity);
                showWarning(type, data.penalty_applied || 0);
            }
        } catch (e) {
            console.error('Failed to report violation:', e);
        }
    }

    function updateIntegrityUI(score) {
        currentIntegrity = score;
        const scoreEl = document.getElementById('integrity-score');
        const barEl = document.getElementById('integrity-bar');

        scoreEl.textContent = score + '%';
        barEl.style.width = score + '%';

        // Color coding
        if (score >= 80) {
            barEl.className = 'bg-green-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-green-600';
        } else if (score >= 50) {
            barEl.className = 'bg-yellow-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-yellow-600';
        } else {
            barEl.className = 'bg-red-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-red-600';
        }
    }

    function showWarning(type, penalty) {
        const warningEl = document.getElementById('cheat-warning');
        const textEl = document.getElementById('warning-text');

        const messages = {
            'tab_switch': `Anda berpindah tab! Skor dikurangi ${penalty} poin.`,
            'split_screen': `Split screen terdeteksi! Skor dikurangi ${penalty} poin.`,
            'window_blur': `Anda keluar dari halaman ujian! Skor dikurangi ${penalty} poin.`,
        };

        textEl.textContent = messages[type] || `Pelanggaran terdeteksi! -${penalty} poin.`;
        warningEl.classList.remove('hidden');

        clearTimeout(warningTimeout);
        warningTimeout = setTimeout(() => {
            warningEl.classList.add('hidden');
        }, 5000);
    }

    // ---- DETECTION 1: Tab Switch (visibilitychange) ----
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            blurStart = Date.now();
        } else if (blurStart) {
            const duration = (Date.now() - blurStart) / 1000; // seconds
            blurStart = null;
            reportViolation('tab_switch', duration);
        }
    });

    // ---- DETECTION 2: Window Blur (floating apps, clicking outside) ----
    window.addEventListener('blur', () => {
        blurStart = Date.now();
    });

    window.addEventListener('focus', () => {
        if (blurStart) {
            const duration = (Date.now() - blurStart) / 1000;
            blurStart = null;

            // Only report if > 1 second (avoid false positives)
            if (duration > 1) {
                reportViolation('tab_switch', duration);
            }
        }
    });

    // ---- DETECTION 3: Prevent right-click & copy ----
    document.addEventListener('contextmenu', (e) => e.preventDefault());
    document.addEventListener('copy', (e) => e.preventDefault());
    document.addEventListener('cut', (e) => e.preventDefault());

    // ---- DETECTION 4: Prevent keyboard shortcuts ----
    document.addEventListener('keydown', (e) => {
        // Ctrl+C, Ctrl+V, Ctrl+U, Ctrl+A, F12, Ctrl+Shift+I
        if ((e.ctrlKey && ['c', 'v', 'u', 'a'].includes(e.key.toLowerCase())) ||
            (e.key === 'F12') ||
            (e.ctrlKey && e.shiftKey && e.key === 'I')) {
            e.preventDefault();
            reportViolation('split_screen', 0);
        }
    });

    // Initialize UI
    updateIntegrityUI(currentIntegrity);
</script>
@endsection
