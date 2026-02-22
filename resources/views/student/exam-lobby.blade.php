@extends('layouts.app-custom')
@section('title', 'Lobby - ' . $exam->title)

@section('content')
<div class="max-w-2xl mx-auto">
    <div id="lobby-data"
         data-exam-id="{{ $exam->id }}"
         data-session-id="{{ $session->id }}"
         data-poll-url="{{ route('exam.pollStatus', $exam) }}"
         data-exam-url="{{ route('student.exam.take', $exam) }}">
    </div>

    {{-- Exam Info Card --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 text-white">
            <h1 class="text-2xl font-bold">{{ $exam->title }}</h1>
            <p class="mt-1 text-indigo-100">Durasi: {{ $exam->duration_minutes }} menit</p>
        </div>

        {{-- Rules --}}
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Peraturan Ujian</h2>
            <ul class="space-y-3">
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-sm font-bold">✕</span>
                    <span class="text-gray-700"><strong>Dilarang pindah tab.</strong> Setiap perpindahan tab akan mengurangi skor integritas.</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-sm font-bold">✕</span>
                    <span class="text-gray-700"><strong>Dilarang membuka aplikasi lain.</strong> Split screen / floating app terdeteksi otomatis.</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-sm font-bold">✕</span>
                    <span class="text-gray-700"><strong>Layar harus tetap fokus.</strong> Jangan minimize atau klik di luar halaman ujian.</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm font-bold">✓</span>
                    <span class="text-gray-700"><strong>Skor integritas dimulai dari 100.</strong> Pelanggaran mengurangi skor secara otomatis.</span>
                </li>
            </ul>
        </div>

        {{-- DND Warning --}}
        <div class="px-8 py-5 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-200">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-amber-900">PENTING: Aktifkan Mode Jangan Ganggu (DND)</h3>
                    <p class="mt-1 text-sm text-amber-800">Sebelum ujian dimulai, <strong class="underline">WAJIB nyalakan mode Do Not Disturb / Jangan Ganggu</strong> di perangkat Anda.</p>
                    <div class="mt-3 space-y-1.5">
                        <p class="text-xs text-amber-700 flex items-center gap-2">
                            <span class="font-bold">Android:</span> Geser ke bawah → aktifkan "Jangan Ganggu"
                        </p>
                        <p class="text-xs text-amber-700 flex items-center gap-2">
                            <span class="font-bold">iPhone:</span> Settings → Focus → Do Not Disturb → ON
                        </p>
                        <p class="text-xs text-amber-700 flex items-center gap-2">
                            <span class="font-bold">💻 Windows:</span> Klik ikon notifikasi → aktifkan "Focus Assist"
                        </p>
                        <p class="text-xs text-amber-700 flex items-center gap-2">
                            <span class="font-bold">🖥️ Mac:</span> Control Center → Focus → Do Not Disturb
                        </p>
                    </div>
                    <div class="mt-3 p-2.5 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-xs font-bold text-red-700">🚨 Jika notifikasi masuk dan mengganggu ujian, hal tersebut dapat tercatat sebagai aktivitas mencurigakan. Nyalakan DND untuk melindungi skor integritas Anda!</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Waiting Status --}}
        <div class="px-8 py-10 text-center" id="lobby-status">
            {{-- Waiting State --}}
            <div id="waiting-state">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full mb-4">
                    <svg class="w-10 h-10 text-yellow-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Menunggu Admin memulai ujian...</h3>
                <p class="mt-2 text-gray-500">Tetap di halaman ini. Ujian akan dimulai otomatis.</p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <span class="w-2 h-2 bg-yellow-500 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                    <span class="w-2 h-2 bg-yellow-500 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                    <span class="w-2 h-2 bg-yellow-500 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                </div>
            </div>

            {{-- Countdown State (Hidden by default) --}}
            <div id="countdown-state" class="hidden">
                <div class="inline-flex items-center justify-center w-32 h-32 bg-indigo-100 rounded-full mb-4">
                    <span id="countdown-number" class="text-6xl font-black text-indigo-600">5</span>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Ujian dimulai dalam...</h3>
                <p class="mt-2 text-gray-500">Bersiaplah!</p>
            </div>
        </div>
    </div>
</div>

<script>
    const dataEl = document.getElementById('lobby-data');
    const examId = Number(dataEl.dataset.examId);
    const sessionId = Number(dataEl.dataset.sessionId);
    const pollUrl = dataEl.dataset.pollUrl;
    const examUrl = dataEl.dataset.examUrl;

    let polling = null;

    function startPolling() {
        polling = setInterval(async () => {
            // Skip polling when tab is hidden
            if (document.hidden) return;
            try {
                const res = await fetch(pollUrl);
                const data = await res.json();

                if (data.exam_status === 'countdown' || data.exam_status === 'started') {
                    clearInterval(polling);
                    startCountdown();
                }
            } catch (e) {
                console.error('Poll error:', e);
            }
        }, 3000); // Poll every 3 seconds (balanced: responsive enough for countdown detection)
    }

    function startCountdown() {
        document.getElementById('waiting-state').classList.add('hidden');
        document.getElementById('countdown-state').classList.remove('hidden');

        let count = 5;
        const numberEl = document.getElementById('countdown-number');
        numberEl.textContent = count;

        const interval = setInterval(() => {
            count--;
            numberEl.textContent = count;

            if (count <= 0) {
                clearInterval(interval);
                // Redirect to exam page
                window.location.href = examUrl;
            }
        }, 1000);
    }

    // Start polling when page loads
    startPolling();
</script>
@endsection
