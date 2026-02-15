@extends('layouts.app-custom')
@section('title', 'Lobby - ' . $exam->title)

@section('content')
<div class="max-w-2xl mx-auto">
    {{-- Exam Info Card --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 text-white">
            <h1 class="text-2xl font-bold">{{ $exam->title }}</h1>
            <p class="mt-1 text-indigo-100">Durasi: {{ $exam->duration_minutes }} menit</p>
        </div>

        {{-- Rules --}}
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">📋 Peraturan Ujian</h2>
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
    const examId = @json($exam->id);
    const sessionId = @json($session->id);
    const pollUrl = "{{ route('exam.pollStatus', $exam) }}";
    const examUrl = "{{ route('student.exam.take', $exam) }}";

    let polling = null;

    function startPolling() {
        polling = setInterval(async () => {
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
        }, 2000); // Poll every 2 seconds
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
